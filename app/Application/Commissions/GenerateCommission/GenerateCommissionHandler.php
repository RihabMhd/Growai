<?php

namespace App\Application\Commissions\GenerateCommission;

use App\Domain\Commissions\Models\Commission;
use App\Domain\Commissions\Services\CommissionCalculator;
use App\Domain\Orders\Models\Order;
use App\Domain\Teams\Models\User;

final class GenerateCommissionHandler
{
    public function __construct(
        private readonly CommissionCalculator $calculator,
    ) {}


    public function handle(GenerateCommissionCommand $command): float
    {
        $order = Order::findOrFail($command->orderId);

        // skip if no agent or already paid
        if (! $order->assigned_to || $order->commission_paid) {
            return 0.00;
        }

        $agent = User::find($order->assigned_to);

        // trigger status must match the agent rule
        if (! $this->shouldTrigger($agent, $command->newStatus)) {
            return 0.00;
        }

        $amount = $this->calculator->calculate($agent, $order);

        if ($amount <= 0.00) {
            return 0.00;
        }

        $this->credit($agent, $order, $amount, $command->newStatus);

        return $amount;
    }

    private function shouldTrigger(?User $agent, string $newStatus): bool
    {
        return $agent
            && $agent->role->value === 'staff'
            && $agent->commission_trigger === $newStatus;
    }

    private function credit(User $agent, Order $order, float $amount, string $triggerStatus): void
    {
        $agent->increment('wallet_balance', $amount);

        $order->updateQuietly(['commission_paid' => true]);

        // keep record to allow future reversals
        Commission::create([
            'order_id'       => $order->id,
            'user_id'        => $agent->id,
            'amount'         => $amount,
            'type'           => $agent->commission_type,
            'trigger_status' => $triggerStatus,
            'state'          => 'credited',
        ]);
    }
}