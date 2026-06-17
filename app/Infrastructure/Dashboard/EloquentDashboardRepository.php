<?php

namespace App\Infrastructure\Dashboard;

use App\Domain\Dashboard\Contracts\DashboardRepositoryInterface;
use App\Domain\Dashboard\DateRange;
use App\Domain\Dashboard\DashboardVisibilityPolicy;
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

        // Single aggregate query instead of 6 clones
        $counts = $base->clone()
            ->selectRaw("
                    COUNT(*) as total,
                    SUM(status = 'confirmed') as confirmed,
                    SUM(status IN ('pending','new')) as pending,
                    SUM(status IN ('cancelled','annulled')) as cancelled,
                    SUM(status = 'delivered') as delivered,
                    SUM(CASE WHEN status IN ('confirmed','delivered') THEN total_price ELSE 0 END) as revenue
                ")
            ->first();

        $prevRevenue = Order::whereBetween('created_at', [$range->prevStart, $range->prevEnd])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->when($policy->isRestricted(), fn($q) => $q->where('assigned_to', $policy->restrictedToUserId()))
            ->whereIn('status', ['confirmed', 'delivered'])
            ->sum('total_price');

        $revenue       = (float) $counts->revenue;
        $total         = (int)   $counts->total;
        $confirmed     = (int)   $counts->confirmed;
        $revenueGrowth = $prevRevenue > 0
            ? round((($revenue - $prevRevenue) / $prevRevenue) * 100, 1)
            : ($revenue > 0 ? 100.0 : 0.0);

        $avgConfirmationTime = DB::table('order_histories')
            ->join('orders', 'orders.id', '=', 'order_histories.order_id')
            ->where('order_histories.new_value', 'confirmed')
            ->whereBetween('orders.created_at', [$range->start, $range->end])
            ->when($shopId, fn($q) => $q->where('orders.shop_id', $shopId))
            ->when($policy->isRestricted(), fn($q) => $q->where('orders.assigned_to', $policy->restrictedToUserId()))
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, orders.created_at, order_histories.created_at)) as avg_min')
            ->value('avg_min');

        return [
            'total'                 => $total,
            'confirmed'             => $confirmed,
            'pending'               => (int) $counts->pending,
            'cancelled'             => (int) $counts->cancelled,
            'delivered'             => (int) $counts->delivered,
            'revenue'               => round($revenue, 2),
            'revenue_growth'        => $revenueGrowth,
            'confirmation_rate'     => $total > 0 ? round(($confirmed / $total) * 100) : 0,
            'avg_confirmation_time' => $avgConfirmationTime ? round((float) $avgConfirmationTime, 1) : null,
        ];
    }

    public function getShops(?int $teamId = null): \Illuminate\Support\Collection
    {
        return Shop::when($teamId, fn($q) => $q->where('team_id', $teamId))
            ->orderBy('name')
            ->get();
    }

    public function getProductCount(DashboardVisibilityPolicy $policy): int
    {
        return Product::when(
            $policy->isRestricted(),
            fn($q) => $q->whereIn(
                'id',
                fn($sub) =>
                $sub->select('product_id')->from('product_user')->where('user_id', $policy->restrictedToUserId())
            )
        )->count();
    }

    public function getClientCount(DashboardVisibilityPolicy $policy): int
    {
        return Client::when(
            $policy->isRestricted(),
            fn($q) => $q->whereHas('orders', fn($o) => $o->where('assigned_to', $policy->restrictedToUserId()))
        )->count();
    }

    public function getTeamMemberCount(?int $teamId): int
    {
        return $teamId
            ? User::where('team_id', $teamId)->where('is_active', true)->count()
            : User::where('is_active', true)->count(); // agency-wide fallback
    }
}
