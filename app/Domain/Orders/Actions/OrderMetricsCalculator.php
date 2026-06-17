<?php

namespace App\Domain\Orders\Actions;

use Illuminate\Database\Eloquent\Builder;

/**
 * Calculates dashboard metrics from a pre-filtered Order query.
 *
 * Receives a cloneable Builder so it never re-applies filters itself.
 * Returns a plain array — no HTTP, no Eloquent side-effects.
 *
 * Usage in ListOrdersHandler:
 *   $metrics = (new OrderMetricsCalculator)->calculate($filteredQuery);
 */
class OrderMetricsCalculator
{
    /**
     * Statuses considered "confirmed" for the confirmation rate.
     */
    private const CONFIRMED_STATUSES = ['confirmed', 'delivered', 'processing', 'shipped'];

    /**
     * Statuses considered "cancelled".
     */
    private const CANCELLED_STATUSES = ['cancelled', 'returned'];

    /**
     * @return array{
     *   total_orders: int,
     *   confirmed: int,
     *   cancelled: int,
     *   pending: int,
     *   confirmation_rate: string
     * }
     */
    public function calculate(Builder $query): array
    {
        $total     = (clone $query)->count();
        $confirmed = (clone $query)->whereIn('status', self::CONFIRMED_STATUSES)->count();
        $cancelled = (clone $query)->whereIn('status', self::CANCELLED_STATUSES)->count();
        $pending = (clone $query)
            ->whereIn('status', ['nouveau', 'pending'])
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
