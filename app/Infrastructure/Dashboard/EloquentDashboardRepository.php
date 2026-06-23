<?php

namespace App\Infrastructure\Dashboard;

use App\Domain\Dashboard\Contracts\DashboardRepositoryInterface;
use App\Domain\Dashboard\DateRange;
use App\Domain\Dashboard\DashboardVisibilityPolicy;
use App\Domain\Dashboard\DashboardStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Products\Models\Product;
use App\Domain\Clients\Models\Client;
use App\Domain\Shopify\Models\Shop;
use App\Domain\Teams\Models\User;
use Illuminate\Support\Facades\DB;

final class EloquentDashboardRepository implements DashboardRepositoryInterface
{
    public function getOrderStats(DateRange $range, DashboardVisibilityPolicy $policy, ?int $shopId = null, ?int $teamId = null): array
    {
        $base = Order::whereBetween('created_at', [$range->start, $range->end])
            ->when($shopId,  fn($q) => $q->where('shop_id', $shopId))
            ->when($teamId,  fn($q) => $q->whereHas('shop', fn($s) => $s->where('team_id', $teamId)))
            ->when($policy->isRestricted(), fn($q) => $q->where('assigned_to', $policy->restrictedToUserId()));

        $confirmedIn = self::quotedList(DashboardStatus::confirmedStatuses());
        $cancelledIn = self::quotedList(DashboardStatus::cancelledStatuses());
        $pendingIn   = self::quotedList(DashboardStatus::pendingStatuses());
        $revenueIn   = self::quotedList(DashboardStatus::revenueStatuses());

        $counts = $base->clone()
            ->selectRaw("
                    COUNT(*) as total,
                    SUM(status = 'confirmed') as confirmed,
                    SUM(status IN ({$confirmedIn})) as confirmed_for_rate,
                    SUM(status IN ({$pendingIn})) as pending,
                    SUM(status IN ({$cancelledIn})) as cancelled,
                    SUM(status = '" . DashboardStatus::DELIVERED_STATUS . "') as delivered,
                    SUM(CASE WHEN status IN ({$revenueIn}) THEN total_price ELSE 0 END) as revenue
                ")
            ->first();

        $prevRevenue = Order::whereBetween('created_at', [$range->prevStart, $range->prevEnd])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->when($teamId, fn($q) => $q->whereHas('shop', fn($s) => $s->where('team_id', $teamId)))
            ->when($policy->isRestricted(), fn($q) => $q->where('assigned_to', $policy->restrictedToUserId()))
            ->whereIn('status', DashboardStatus::revenueStatuses())
            ->sum('total_price');

        $revenue         = (float) $counts->revenue;
        $total           = (int)   $counts->total;
        $confirmed       = (int)   $counts->confirmed;
        $confirmedForRate = (int)  $counts->confirmed_for_rate;
        $revenueGrowth = $prevRevenue > 0
            ? round((($revenue - $prevRevenue) / $prevRevenue) * 100, 1)
            : ($revenue > 0 ? 100.0 : 0.0);

        $avgConfirmationTime = DB::table('order_histories')
            ->join('orders', 'orders.id', '=', 'order_histories.order_id')
            ->where('order_histories.new_value', 'confirmed')
            ->whereBetween('orders.created_at', [$range->start, $range->end])
            ->when($shopId, fn($q) => $q->where('orders.shop_id', $shopId))
            ->when($teamId, fn($q) => $q->whereIn('orders.shop_id', fn($sub) => $sub->select('id')->from('shops')->where('team_id', $teamId)))
            ->when($policy->isRestricted(), fn($q) => $q->where('orders.assigned_to', $policy->restrictedToUserId()))
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, orders.created_at, order_histories.created_at)) as avg_min')
            ->value('avg_min');

        $deliveredCount = (int) $counts->delivered;

        return [
            'total'                 => $total,
            'confirmed'             => $confirmed,
            'pending'               => (int) $counts->pending,
            'cancelled'             => (int) $counts->cancelled,
            'delivered'             => $deliveredCount,
            'revenue'               => round($revenue, 2),
            'revenue_growth'        => $revenueGrowth,
            'confirmation_rate'     => $total > 0 ? round(($confirmedForRate / $total) * 100) : 0,
            'delivery_rate'         => $total > 0 ? round(($deliveredCount / $total) * 100) : 0,
            'avg_confirmation_time' => $avgConfirmationTime ? round((float) $avgConfirmationTime, 1) : null,
        ];
    }

    public function getShopsStats(DateRange $range, DashboardVisibilityPolicy $policy, array $shopIds, ?int $teamId = null): \Illuminate\Support\Collection
    {
        if (empty($shopIds)) {
            return collect();
        }

        $base = Order::whereBetween('created_at', [$range->start, $range->end])
            ->whereIn('shop_id', $shopIds)
            ->when($teamId, fn($q) => $q->whereHas('shop', fn($s) => $s->where('team_id', $teamId)))
            ->when($policy->isRestricted(), fn($q) => $q->where('assigned_to', $policy->restrictedToUserId()));

        $confirmedIn = self::quotedList(DashboardStatus::confirmedStatuses());
        $cancelledIn = self::quotedList(DashboardStatus::cancelledStatuses());
        $pendingIn   = self::quotedList(DashboardStatus::pendingStatuses());
        $revenueIn   = self::quotedList(DashboardStatus::revenueStatuses());

        $rows = $base->clone()
            ->selectRaw("
                    shop_id,
                    COUNT(*) as total,
                    SUM(status = 'confirmed') as confirmed,
                    SUM(status IN ({$confirmedIn})) as confirmed_for_rate,
                    SUM(status IN ({$pendingIn})) as pending,
                    SUM(status IN ({$cancelledIn})) as cancelled,
                    SUM(status = '" . DashboardStatus::DELIVERED_STATUS . "') as delivered,
                    SUM(CASE WHEN status IN ({$revenueIn}) THEN total_price ELSE 0 END) as revenue
                ")
            ->groupBy('shop_id')
            ->get()
            ->keyBy('shop_id');

        $avgTimes = DB::table('order_histories')
            ->join('orders', 'orders.id', '=', 'order_histories.order_id')
            ->where('order_histories.new_value', 'confirmed')
            ->whereBetween('orders.created_at', [$range->start, $range->end])
            ->whereIn('orders.shop_id', $shopIds)
            ->when($teamId, fn($q) => $q->whereIn('orders.shop_id', fn($sub) => $sub->select('id')->from('shops')->where('team_id', $teamId)))
            ->when($policy->isRestricted(), fn($q) => $q->where('orders.assigned_to', $policy->restrictedToUserId()))
            ->selectRaw('orders.shop_id, AVG(TIMESTAMPDIFF(MINUTE, orders.created_at, order_histories.created_at)) as avg_min')
            ->groupBy('orders.shop_id')
            ->get()
            ->keyBy('shop_id');

        $prevRevenues = Order::whereBetween('created_at', [$range->prevStart, $range->prevEnd])
            ->whereIn('shop_id', $shopIds)
            ->when($teamId, fn($q) => $q->whereHas('shop', fn($s) => $s->where('team_id', $teamId)))
            ->when($policy->isRestricted(), fn($q) => $q->where('assigned_to', $policy->restrictedToUserId()))
            ->whereIn('status', DashboardStatus::revenueStatuses())
            ->selectRaw('shop_id, SUM(total_price) as prev_revenue')
            ->groupBy('shop_id')
            ->get()
            ->keyBy('shop_id');

        $abandonedRows = Order::whereBetween('created_at', [$range->start, $range->end])
            ->whereIn('shop_id', $shopIds)
            ->when($teamId, fn($q) => $q->whereHas('shop', fn($s) => $s->where('team_id', $teamId)))
            ->when($policy->isRestricted(), fn($q) => $q->where('assigned_to', $policy->restrictedToUserId()))
            ->selectRaw("
                    shop_id,
                    SUM(CASE WHEN is_abandoned = 1 THEN 1 ELSE 0 END) as abandoned_orders,
                    SUM(CASE WHEN status = '" . DashboardStatus::RECOVERED_STATUS . "' THEN 1 ELSE 0 END) as recovered_orders,
                    SUM(CASE WHEN is_abandoned = 1 THEN total_price ELSE 0 END) as lost_revenue,
                    SUM(CASE WHEN status = '" . DashboardStatus::RECOVERED_STATUS . "' THEN total_price ELSE 0 END) as recovered_revenue
                ")
            ->groupBy('shop_id')
            ->get()
            ->keyBy('shop_id');

        return $rows->map(function ($row) use ($avgTimes, $prevRevenues, $abandonedRows) {
            $shopId           = (int) $row->shop_id;
            $revenue          = (float) $row->revenue;
            $total            = (int) $row->total;
            $confirmed        = (int) $row->confirmed;
            $confirmedForRate  = (int) $row->confirmed_for_rate;
            $prevRev  = (float) ($prevRevenues->get($shopId)->prev_revenue ?? 0);
            $revenueGrowth = $prevRev > 0
                ? round((($revenue - $prevRev) / $prevRev) * 100, 1)
                : ($revenue > 0 ? 100.0 : 0.0);

            $abandoned       = $abandonedRows->get($shopId);
            $abandonedOrders = (int) ($abandoned->abandoned_orders ?? 0);
            $recoveredOrders = (int) ($abandoned->recovered_orders ?? 0);
            $recoverable     = $abandonedOrders + $recoveredOrders;

            $deliveredCount = (int) $row->delivered;

            return [
                'total'                 => $total,
                'confirmed'             => $confirmed,
                'pending'               => (int) $row->pending,
                'cancelled'             => (int) $row->cancelled,
                'delivered'             => $deliveredCount,
                'revenue'               => round($revenue, 2),
                'revenue_growth'        => $revenueGrowth,
                'confirmation_rate'     => $total > 0 ? round(($confirmedForRate / $total) * 100) : 0,
                'delivery_rate'         => $total > 0 ? round(($deliveredCount / $total) * 100) : 0,
                'avg_confirmation_time' => $avgTimes->get($shopId)?->avg_min
                    ? round((float) $avgTimes->get($shopId)->avg_min, 1)
                    : null,
                'abandoned_analytics'   => [
                    'abandoned_orders'  => $abandonedOrders,
                    'recovered_orders'  => $recoveredOrders,
                    'recovery_rate'     => $recoverable > 0
                        ? round(($recoveredOrders / $recoverable) * 100, 2)
                        : 0.0,
                    'lost_revenue'      => round((float) ($abandoned->lost_revenue ?? 0), 2),
                    'recovered_revenue' => round((float) ($abandoned->recovered_revenue ?? 0), 2),
                ],
            ];
        });
    }

    public function getShops(?int $teamId = null): \Illuminate\Support\Collection
    {
        return Shop::when($teamId, fn($q) => $q->where('team_id', $teamId))
            ->orderBy('name')
            ->get();
    }

    public function getProductCount(DashboardVisibilityPolicy $policy, ?int $teamId = null): int
    {
        return Product::when($teamId, fn($q) => $q->whereHas('shop', fn($s) => $s->where('team_id', $teamId)))
            ->when(
                $policy->isRestricted(),
                fn($q) => $q->whereIn(
                    'id',
                    fn($sub) =>
                    $sub->select('product_id')->from('product_user')->where('user_id', $policy->restrictedToUserId())
                )
            )->count();
    }

    public function getClientCount(DashboardVisibilityPolicy $policy, ?int $teamId = null): int
    {
        return Client::when($teamId, fn($q) => $q->whereHas('orders.shop', fn($s) => $s->where('team_id', $teamId)))
            ->when(
                $policy->isRestricted(),
                fn($q) => $q->whereHas('orders', fn($o) => $o->where('assigned_to', $policy->restrictedToUserId()))
            )->count();
    }

    public function getTeamMemberCount(?int $teamId): int
    {
        return $teamId
            ? User::where('team_id', $teamId)->where('is_active', true)->count()
            : User::where('is_active', true)->count();
    }

    private static function quotedList(array $values): string
    {
        return "'" . implode("','", $values) . "'";
    }
}
