<?php

namespace App\Application\Orders\ListAbandonedOrders;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Services\OrderVisibilityResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class ListAbandonedOrdersHandler
{
    public function __construct(
        private readonly OrderVisibilityResolver $visibilityResolver,
    ) {}

    public function handle(ListAbandonedOrdersQuery $query): array
    {
        $base = $this->baseQuery($query);
        $listQuery = clone $base;
        $this->applyStatusFilter($listQuery, $query->status);
        $kpis = $this->kpis(clone $listQuery);

        $orders = $listQuery
            ->with(['client', 'items.product'])
            ->withCount([
                'messages as recovery_message_count' => fn (Builder $messages) => $this->recoveryMessages($messages),
            ])
            ->withMax([
                'messages as last_recovery_attempt_at' => fn (Builder $messages) => $this->recoveryMessages($messages),
            ], 'sent_at')
            ->orderByDesc(DB::raw('COALESCE(abandoned_at, created_at)'))
            ->paginate($query->perPage, ['*'], 'page', $query->page);

        return [
            'data' => $orders->getCollection()->map(fn (Order $order) => $this->mapOrder($order))->values(),
            'kpis' => $kpis,
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ];
    }

    private function baseQuery(ListAbandonedOrdersQuery $query): Builder
    {
        $builder = Order::query()
            ->where(function (Builder $orders) {
                $orders->where('is_abandoned', true)
                    ->orWhere('status', 'recovered');
            });

        $this->visibilityResolver->apply($builder, $query->actor);
        $this->applyPeriod($builder, $query->period);
        $this->applyHasPhone($builder, $query->hasPhone);
        $this->applySearch($builder, $query->search);

        return $builder;
    }

    private function applyPeriod(Builder $builder, string $period): void
    {
        $cutoff = match ($period) {
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            'all' => null,
            default => now()->subDays(30),
        };

        if ($cutoff instanceof Carbon) {
            $builder->where(fn (Builder $orders) => $orders
                ->where('abandoned_at', '>=', $cutoff)
                ->orWhere(fn (Builder $fallback) => $fallback
                    ->whereNull('abandoned_at')
                    ->where('created_at', '>=', $cutoff)));
        }
    }

    private function applyHasPhone(Builder $builder, bool $hasPhone): void
    {
        if (! $hasPhone) {
            return;
        }

        $builder->whereHas('client', fn (Builder $client) => $client
            ->whereNotNull('phone')
            ->where('phone', '<>', ''));
    }

    private function applySearch(Builder $builder, ?string $search): void
    {
        if ($search === null) {
            return;
        }

        $builder->whereHas('client', function (Builder $client) use ($search) {
            $client->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    private function applyStatusFilter(Builder $builder, ?string $status): void
    {
        match ($status) {
            'open' => $builder->where('status', 'abandoned')->where('is_abandoned', true),
            'recovered' => $builder->where('status', 'recovered'),
            'recovery_sent' => $builder
                ->where('status', 'abandoned')
                ->where('is_abandoned', true)
                ->whereHas('messages', fn (Builder $messages) => $this->recoveryMessages($messages)),
            default => null,
        };
    }

    private function recoveryMessages(Builder $messages): Builder
    {
        return $messages
            ->where('channel', 'whatsapp')
            ->where('direction', 'outgoing');
    }

    private function kpis(Builder $base): array
    {
        $row = (clone $base)
            ->selectRaw("
                SUM(CASE WHEN status = 'abandoned' AND is_abandoned = 1 THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN status = 'abandoned' AND is_abandoned = 1 THEN total_price ELSE 0 END) as open_revenue,
                SUM(CASE WHEN status = 'recovered' THEN 1 ELSE 0 END) as recovered_count,
                SUM(CASE WHEN status = 'recovered' THEN total_price ELSE 0 END) as recovered_revenue
            ")
            ->first();

        $openCount = (int) ($row->open_count ?? 0);
        $recoveredCount = (int) ($row->recovered_count ?? 0);
        $totalAttempts = $openCount + $recoveredCount;

        $messageCount = DB::table('messages')
            ->whereIn('order_id', (clone $base)->select('orders.id'))
            ->where('messages.channel', 'whatsapp')
            ->where('messages.direction', 'outgoing')
            ->count('messages.id');

        return [
            'open_count' => $openCount,
            'open_revenue' => round((float) ($row->open_revenue ?? 0), 2),
            'recovered_count' => $recoveredCount,
            'recovered_revenue' => round((float) ($row->recovered_revenue ?? 0), 2),
            'recovery_rate' => $totalAttempts > 0 ? round(($recoveredCount / $totalAttempts) * 100, 1) : 0.0,
            'total_attempts' => $totalAttempts,
            'recovery_sent_count' => $messageCount,
        ];
    }

    private function mapOrder(Order $order): array
    {
        $items = $order->items->map(fn ($item) => [
            'id' => $item->id,
            'product_name' => $item->product_name,
            'quantity' => (int) $item->quantity,
            'total_price' => (float) $item->total_price,
        ]);

        $recoveryCount = (int) ($order->recovery_message_count ?? 0);

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $this->displayStatus($order, $recoveryCount),
            'raw_status' => $order->status,
            'total_price' => (float) $order->total_price,
            'currency' => $order->currency,
            'abandoned_at' => optional($order->abandoned_at ?? $order->created_at)->toISOString(),
            'created_at' => optional($order->created_at)->toISOString(),
            'customer' => [
                'name' => $order->client?->name,
                'email' => $order->client?->email,
                'phone' => $order->client?->phone,
            ],
            'items_count' => $items->count(),
            'item_summary' => $items->take(2)->map(fn ($item) => "{$item['product_name']} x{$item['quantity']}")->implode(', '),
            'items' => $items,
            'recovery_message_count' => $recoveryCount,
            'last_recovery_attempt_at' => $order->last_recovery_attempt_at
                ? Carbon::parse($order->last_recovery_attempt_at)->toISOString()
                : null,
        ];
    }

    private function displayStatus(Order $order, int $recoveryCount): string
    {
        if ($order->status === 'recovered') {
            return 'recovered';
        }

        if ($recoveryCount > 0) {
            return 'recovery_sent';
        }

        return 'open';
    }
}
