<?php

namespace App\Domain\Dispatch\Services;

use App\Domain\Orders\Models\Order;
use App\Domain\Teams\Models\User;

final class DispatchEngine
{
    public function __construct(
        private readonly ProductMatcher  $productMatcher,
        private readonly QuotaRoundRobin $quotaRoundRobin,
    ) {}

    public function selectAgent(Order $order): ?User
    {
        $orderProductIds = $order->items()->pluck('product_id')->filter()->toArray();

        // Load all candidate agents with their product relationships
        $agents = User::where('role', 'staff')
            ->where('is_active', true)
            ->where('is_dispatch_active', true)
            ->where('quota', '>', 0)
            ->with('products')
            ->get();

        if ($agents->isEmpty()) {
            return null;
        }

        $eligible = $this->productMatcher->filter($agents, $orderProductIds);

        if ($eligible->isEmpty()) {
            return null;
        }

        // Fetch current assignment counts for eligible agents only
        $agentOrderCounts = Order::whereIn('assigned_to', $eligible->pluck('id'))
            ->selectRaw('assigned_to, count(*) as count')
            ->groupBy('assigned_to')
            ->pluck('count', 'assigned_to')
            ->toArray();

        return $this->quotaRoundRobin->select($eligible, $agentOrderCounts);
    }
}