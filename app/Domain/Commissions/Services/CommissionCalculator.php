<?php

namespace App\Domain\Commissions\Services;

use App\Domain\Orders\Models\Order;
use App\Domain\Teams\Models\User;

final class CommissionCalculator
{

    public function calculate(User $agent, Order $order): float
    {
        return match ($agent->commission_type) {
            'fixed'   => (float) $agent->commission_amount,
            'percent' => ((float) $agent->commission_amount / 100) * (float) $order->total_price,
            default   => 0.00,
        };
    }
}