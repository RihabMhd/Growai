<?php

namespace App\Domain\Orders\Services;

use App\Domain\Orders\Models\Order;
use App\Models\Team;
use App\Models\User;

/**
 * Pure domain service responsible for selecting the best eligible agent
 * for a newly created order using a proportional round-robin algorithm.
 *
 * Has no knowledge of HTTP, events, or observers.
 * Call this explicitly from CreateOrderHandler after persisting the order.
 */
class AutoDispatchService
{
    /**
     * Attempt to auto-assign the given order to the most eligible agent.
     * Returns the selected agent, or null if no eligible agent is found.
     */
    public function dispatch(Order $order): ?User
    {
        // 1. Check team auto-dispatch setting
        $team = Team::first();
        if (! $team || ! $team->dispatch_auto) {
            return null;
        }

        // 2. Extract product IDs from the order
        $orderProductIds = $order->items()->pluck('product_id')->filter()->toArray();

        // 3. Load all active dispatch-enabled agents with quota
        $agents = User::where('role', 'staff')
            ->where('is_active', true)
            ->where('is_dispatch_active', true)
            ->where('quota', '>', 0)
            ->with('products')
            ->get();

        if ($agents->isEmpty()) {
            return null;
        }

        // 4. Filter by product visibility
        $eligibleAgents = $agents->filter(
            fn ($agent) => $this->isEligible($agent, $orderProductIds)
        );

        if ($eligibleAgents->isEmpty()) {
            return null;
        }

        // 5. Fetch current assignment counts for eligible agents
        $agentOrderCounts = Order::whereIn('assigned_to', $eligibleAgents->pluck('id'))
            ->selectRaw('assigned_to, count(*) as count')
            ->groupBy('assigned_to')
            ->pluck('count', 'assigned_to')
            ->toArray();

        // 6. Prefer agents under quota; fall back to all eligible
        $underQuota = $eligibleAgents->filter(
            fn ($agent) => ($agentOrderCounts[$agent->id] ?? 0) < $agent->quota
        );

        $candidates = $underQuota->isNotEmpty() ? $underQuota : $eligibleAgents;

        return $this->selectByLowestRatio($candidates, $agentOrderCounts);
    }

    /**
     * An agent is eligible if they have no product restrictions,
     * or if at least one of their products matches the order.
     */
    private function isEligible(User $agent, array $orderProductIds): bool
    {
        $assignedProductIds = $agent->products->pluck('id')->toArray();

        if (empty($assignedProductIds)) {
            return true;
        }

        return ! empty(array_intersect($orderProductIds, $assignedProductIds));
    }

    /**
     * Select the candidate with the lowest assignment/quota ratio.
     * Ties are broken by absolute assignment count (fewest wins).
     */
    private function selectByLowestRatio($candidates, array $agentOrderCounts): ?User
    {
        $selectedAgent = null;
        $lowestRatio   = null;

        foreach ($candidates as $agent) {
            $currentCount = $agentOrderCounts[$agent->id] ?? 0;
            $ratio        = $currentCount / $agent->quota;

            if ($selectedAgent === null || $ratio < $lowestRatio) {
                $selectedAgent = $agent;
                $lowestRatio   = $ratio;
            } elseif ($ratio == $lowestRatio) {
                $currentSelectedCount = $agentOrderCounts[$selectedAgent->id] ?? 0;
                if ($currentCount < $currentSelectedCount) {
                    $selectedAgent = $agent;
                }
            }
        }

        return $selectedAgent;
    }
}