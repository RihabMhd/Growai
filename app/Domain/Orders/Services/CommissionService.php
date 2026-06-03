<?php

namespace App\Domain\Orders\Services;

use App\Domain\Orders\Models\Order;
use App\Domain\Teams\Models\User;

/**
 * Pure domain service responsible for calculating and crediting
 * agent commissions when an order reaches the agent's trigger status.
 *
 * Has no knowledge of HTTP, observers, or events.
 * Call this from a ProcessCommission listener on the OrderStatusChanged event.
 */
class CommissionService
{
    /**
     * Process commission for the given order and new status.
     *
     * Returns the credited amount, or 0.00 if no commission was due.
     */
    public function processForOrder(Order $order, string $newStatus): float
    {
        // No agent, or commission already paid — skip
        if (! $order->assigned_to || $order->commission_paid) {
            return 0.00;
        }

        $agent = User::find($order->assigned_to);

        if (! $this->shouldTrigger($agent, $newStatus)) {
            return 0.00;
        }

        $amount = $this->calculate($agent, $order);

        if ($amount <= 0.00) {
            return 0.00;
        }

        $this->credit($agent, $order, $amount);

        return $amount;
    }

    /**
     * Determine whether the commission trigger condition is met.
     */
    private function shouldTrigger(?User $agent, string $newStatus): bool
    {
        return $agent
            && $agent->role === 'staff'
            && $agent->commission_trigger === $newStatus;
    }

    /**
     * Calculate the commission amount based on the agent's commission type.
     */
    private function calculate(User $agent, Order $order): float
    {
        return match ($agent->commission_type) {
            'fixed'   => (float) $agent->commission_amount,
            'percent' => ((float) $agent->commission_amount / 100) * (float) $order->total_price,
            default   => 0.00,
        };
    }

    /**
     * Credit the agent's wallet and mark the order commission as paid.
     * Uses updateQuietly to avoid re-triggering the observer update hook.
     */
    private function credit(User $agent, Order $order, float $amount): void
    {
        $agent->increment('wallet_balance', $amount);

        $order->updateQuietly(['commission_paid' => true]);
    }
}