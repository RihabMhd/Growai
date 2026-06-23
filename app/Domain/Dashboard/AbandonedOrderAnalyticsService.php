<?php

namespace App\Domain\Dashboard;

use App\Domain\Orders\Models\Order;

final class AbandonedOrderAnalyticsService
{
    /**
     * @return array{
     *   abandoned_orders:int,
     *   recovered_orders:int,
     *   recovery_rate:float,
     *   lost_revenue:float,
     *   recovered_revenue:float
     * }
     */
    public function calculate(
        DateRange $range,
        DashboardVisibilityPolicy $policy,
        ?int $shopId = null,
        ?int $teamId = null,
    ): array {
        $base = Order::query()
            ->whereBetween('created_at', [$range->start, $range->end])
            ->when($shopId, fn ($query) => $query->where('shop_id', $shopId))
            ->when($teamId, fn ($query) => $query->whereHas('shop', fn ($shop) => $shop->where('team_id', $teamId)))
            ->when($policy->isRestricted(), fn ($query) => $query->where('assigned_to', $policy->restrictedToUserId()));

        $row = $base
            ->selectRaw("
                SUM(CASE WHEN is_abandoned = 1 THEN 1 ELSE 0 END) as abandoned_orders,
                SUM(CASE WHEN status = 'recovered' THEN 1 ELSE 0 END) as recovered_orders,
                SUM(CASE WHEN is_abandoned = 1 THEN total_price ELSE 0 END) as lost_revenue,
                SUM(CASE WHEN status = 'recovered' THEN total_price ELSE 0 END) as recovered_revenue
            ")
            ->first();

        $abandonedOrders = (int) ($row->abandoned_orders ?? 0);
        $recoveredOrders = (int) ($row->recovered_orders ?? 0);
        $recoverableOrders = $abandonedOrders + $recoveredOrders;

        return [
            'abandoned_orders' => $abandonedOrders,
            'recovered_orders' => $recoveredOrders,
            'recovery_rate' => $recoverableOrders > 0
                ? round(($recoveredOrders / $recoverableOrders) * 100, 2)
                : 0.0,
            'lost_revenue' => round((float) ($row->lost_revenue ?? 0), 2),
            'recovered_revenue' => round((float) ($row->recovered_revenue ?? 0), 2),
        ];
    }
}
