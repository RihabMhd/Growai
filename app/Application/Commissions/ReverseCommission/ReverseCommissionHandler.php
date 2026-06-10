<?php

namespace App\Application\Commissions\ReverseCommission;

use App\Domain\Commissions\Models\Commission;
use App\Domain\Orders\Models\Order;
use App\Domain\Teams\Models\User;

final class ReverseCommissionHandler
{
    /**
     * Reverses a previously credited commission.
     * No-op if no credited commission record exists for the order.
     *
     * Returns reversed amount, or 0.00 if nothing was reversed.
     */
    public function handle(ReverseCommissionCommand $command): float
    {
        $commission = Commission::where('order_id', $command->orderId)
            ->where('state', 'credited')
            ->first();

        if (! $commission) {
            return 0.00;
        }

        $agent = User::find($commission->user_id);

        if (! $agent) {
            // Agent deleted — mark reversed, skip wallet adjustment
            $commission->update(['state' => 'reversed']);
            return 0.00;
        }

        $agent->decrement('wallet_balance', $commission->amount);

        $commission->update(['state' => 'reversed']);

        Order::where('id', $command->orderId)
            ->update(['commission_paid' => false]);

        return $commission->amount;
    }
}