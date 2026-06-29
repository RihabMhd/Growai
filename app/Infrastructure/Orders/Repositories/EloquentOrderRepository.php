<?php

namespace App\Infrastructure\Orders\Repositories;

use App\Domain\Orders\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

use App\Domain\Orders\Services\OrderStatusValidator;

class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private readonly OrderStatusValidator $orderStatusValidator,
    ) {}
    // standard eager load set
    private const BASE_RELATIONS = [
        'items.product',
        'client',
        'shop',
        'assignedAgent',
        'shipments',
    ];

    private const DETAIL_RELATIONS = [
        'items.product',
        'client',
        'shop',
        'assignedAgent',
        'shipments',
        'histories.user',
        'sources',
    ];


    public function baseQuery(): Builder
    {
        return Order::query()
            ->with(self::BASE_RELATIONS)
            ->orderBy('created_at', 'desc');
    }

    public function findWithRelations(int|string $id): Order
    {
        return Order::with(self::DETAIL_RELATIONS)->findOrFail($id);
    }


    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function update(Order $order, array $data): Order
    {
        $order->update($data);

        return $order->fresh(self::DETAIL_RELATIONS);
    }

    public function assignAgent(Order $order, ?int $agentId): void
    {
        $order->updateQuietly(['assigned_to' => $agentId]);
    }


    public function bulkAssign(array $orderIds, ?int $agentId): Collection
    {
        $orders = Order::with('assignedAgent')->whereIn('id', $orderIds)->get();

        DB::transaction(function () use ($orders, $agentId) {
            foreach ($orders as $order) {
                $order->updateQuietly(['assigned_to' => $agentId]);
            }
        });

        return $orders;
    }

    public function bulkUpdateStatus(array $orderIds, string $newStatus): Collection
    {
        $this->orderStatusValidator->assertExists($newStatus);

        $orders = Order::whereIn('id', $orderIds)->get();


        $changed = new Collection();

        DB::transaction(function () use ($orders, $newStatus, &$changed) {
            foreach ($orders as $order) {
                if ($order->status !== $newStatus) {
                    $order->update(['status' => $newStatus]);
                    $changed->push($order);
                }
            }
        });

        return $changed;
    }
}