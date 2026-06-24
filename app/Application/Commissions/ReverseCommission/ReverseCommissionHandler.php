<?php

namespace App\Application\Commissions\ReverseCommission;

use App\Domain\Commissions\Models\Commission;
use App\Domain\Orders\Models\Order;
use App\Domain\Teams\Models\User;

final class ReverseCommissionHandler
{

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
            // skip wallet adjustment if agent is deleted
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