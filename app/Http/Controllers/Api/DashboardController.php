<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Domain\Clients\Models\Client;
use App\Domain\Orders\Models\Order;
use App\Domain\Products\Models\Product;
use App\Domain\Shopify\Models\Shop;
use App\Domain\Teams\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Resolve period string → [start, end, prevStart, prevEnd] Carbon objects.
     */
    private function resolvePeriod(string $period): array
    {
        $now = Carbon::now();

        switch ($period) {
            case 'yesterday':
                $start = $now->copy()->subDay()->startOfDay();
                $end   = $now->copy()->subDay()->endOfDay();
                break;
            case 'last_7_days':
                $start = $now->copy()->subDays(6)->startOfDay();
                $end   = $now->copy()->endOfDay();
                break;
            case 'this_month':
                $start = $now->copy()->startOfMonth();
                $end   = $now->copy()->endOfDay();
                break;
            case 'today':
            default:
                $start = $now->copy()->startOfDay();
                $end   = $now->copy()->endOfDay();
                break;
        }

        $lengthSeconds = $start->diffInSeconds($end);
        $prevEnd       = $start->copy()->subSecond();
        $prevStart     = $prevEnd->copy()->subSeconds($lengthSeconds);

        return compact('start', 'end', 'prevStart', 'prevEnd');
    }

    /**
     * Build a base Order query filtered by period and optionally by shop.
     * Agents are always restricted to orders assigned to them.
     */
    private function baseQuery(Carbon $start, Carbon $end, ?int $shopId = null)
    {
        $query = Order::whereBetween('created_at', [$start, $end]);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        if (Auth::user()->role === 'agent') {
            $query->where('assigned_to', Auth::id());
        }

        return $query;
    }

    /**
     * Compute all order KPIs for the given period + optional shop scope.
     */
    private function computeStats(
        Carbon $start,
        Carbon $end,
        Carbon $prevStart,
        Carbon $prevEnd,
        ?int   $shopId = null
    ): array {
        $q = fn() => $this->baseQuery($start, $end, $shopId);

        $total     = (clone $q())->count();
        $confirmed = (clone $q())->where('status', 'confirmed')->count();
        $pending   = (clone $q())->whereIn('status', ['pending', 'new'])->count();
        $cancelled = (clone $q())->whereIn('status', ['cancelled', 'annulled'])->count();
        $delivered = (clone $q())->where('status', 'delivered')->count();

        $revenue = (clone $q())
            ->whereIn('status', ['confirmed', 'delivered'])
            ->sum('total_price');

        $confirmationRate = $total > 0 ? round(($confirmed / $total) * 100) : 0;

        // Average minutes between order creation and first history entry = 'confirmed'
        $avgConfirmationTime = DB::table('order_histories')
            ->join('orders', 'orders.id', '=', 'order_histories.order_id')
            ->where('order_histories.new_value', 'confirmed')
            ->whereBetween('orders.created_at', [$start, $end])
            ->when($shopId, fn($q) => $q->where('orders.shop_id', $shopId))
            ->when(
                Auth::user()->role === 'agent',
                fn($q) => $q->where('orders.assigned_to', Auth::id())
            )
            ->selectRaw(
                'AVG(TIMESTAMPDIFF(MINUTE, orders.created_at, order_histories.created_at)) as avg_min'
            )
            ->value('avg_min');

        // Previous period revenue for growth %
        $prevRevenue = $this->baseQuery($prevStart, $prevEnd, $shopId)
            ->whereIn('status', ['confirmed', 'delivered'])
            ->sum('total_price');

        $revenueGrowth = $prevRevenue > 0
            ? round((($revenue - $prevRevenue) / $prevRevenue) * 100, 1)
            : ($revenue > 0 ? 100.0 : 0.0);

        return [
            'total'                 => $total,
            'confirmed'             => $confirmed,
            'pending'               => $pending,
            'cancelled'             => $cancelled,
            'delivered'             => $delivered,
            'revenue'               => round((float) $revenue, 2),
            'revenue_growth'        => $revenueGrowth,
            'confirmation_rate'     => $confirmationRate,
            'avg_confirmation_time' => $avgConfirmationTime
                ? round((float) $avgConfirmationTime, 1)
                : null,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Single endpoint
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/dashboard
     *
     * Query params:
     *   period = today | yesterday | last_7_days | this_month   (default: today)
     *
     * Response:
     * {
     *   "period": "today",
     *   "global": { total, confirmed, pending, cancelled, delivered,
     *               revenue, revenue_growth, confirmation_rate,
     *               avg_confirmation_time,
     *               products, clients, team_members },
     *   "shops": [
     *     { id, name, platform, domain, is_active,
     *       total, confirmed, pending, cancelled, delivered,
     *       revenue, revenue_growth, confirmation_rate,
     *       avg_confirmation_time },
     *     ...
     *   ]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|string|in:today,yesterday,last_7_days,this_month',
        ]);

        $periodKey = $request->input('period', 'today');
        [
            'start'     => $start,
            'end'       => $end,
            'prevStart' => $prevStart,
            'prevEnd'   => $prevEnd,
        ] = $this->resolvePeriod($periodKey);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // ── Global stats (all shops combined) ─────────────────────────────────
        $global = $this->computeStats($start, $end, $prevStart, $prevEnd);

        // Non-period overview counts
        $global['products'] = Product::when(
            $user->role === 'agent',
            fn($q) => $q->whereIn('id', function ($sub) use ($user) {
                $sub->select('product_id')
                    ->from('product_user')
                    ->where('user_id', $user->id);
            })
        )->count();

        $global['clients'] = Client::when(
            $user->role === 'agent',
            fn($q) => $q->whereHas('orders', fn($o) => $o->where('assigned_to', $user->id))
        )->count();

        $global['team_members'] = $user->team_id
            ? User::where('team_id', $user->team_id)->where('is_active', true)->count()
            : 0;

        // ── Per-shop stats ────────────────────────────────────────────────────
        $shops = Shop::orderBy('name')
            ->get()
            ->map(function (Shop $shop) use ($start, $end, $prevStart, $prevEnd) {
                return array_merge(
                    [
                        'id'        => $shop->id,
                        'name'      => $shop->boutique_name ?? $shop->name,
                        'platform'  => $shop->platform,
                        'domain'    => $shop->shopify_domain,
                        'is_active' => $shop->is_active,
                    ],
                    $this->computeStats($start, $end, $prevStart, $prevEnd, $shop->id)
                );
            });

        return response()->json([
            'period' => $periodKey,
            'global' => $global,
            'shops'  => $shops,
        ]);
    }
}