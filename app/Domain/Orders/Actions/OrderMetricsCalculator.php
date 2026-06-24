<?php

namespace App\Domain\Orders\Actions;

use Illuminate\Database\Eloquent\Builder;

class OrderMetricsCalculator
{
    public const CONFIRMED_STATUSES = ['confirmed', 'delivered', 'processing', 'shipped'];

    public const CANCELLED_STATUSES = ['cancelled', 'returned'];

    public function calculate(Builder $query): array
    {
        $total     = (clone $query)->count();
        $confirmed = (clone $query)->whereIn('status', self::CONFIRMED_STATUSES)->count();
        $cancelled = (clone $query)->whereIn('status', self::CANCELLED_STATUSES)->count();
        $pending = (clone $query)
            ->whereIn('status', ['nouveau', 'pending', 'recovered'])
            ->count();

        $rate = $total > 0 ? round(($confirmed / $total) * 100) : 0;

        return [
            'total_orders'      => $total,
            'confirmed'         => $confirmed,
            'cancelled'         => $cancelled,
            'pending'           => $pending,
            'confirmation_rate' => $rate . '%',
        ];
    }
}
