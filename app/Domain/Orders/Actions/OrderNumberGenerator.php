<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Teams\Models\Team;
use Illuminate\Support\Str;

/**
 * Generates a unique order number using the team's configured prefix.
 *
 * Usage: $generator->generate() → "ORD-A1B2C3D4"
 *
 * Call this BEFORE opening the DB transaction in CreateOrderHandler
 * so the number is ready when Order::create() is called.
 */
class OrderNumberGenerator
{
    public function generate(): string
    {
        $team   = Team::first();
        $prefix = ($team?->order_prefix) ? $team->order_prefix : 'ORD';

        return $prefix . '-' . strtoupper(Str::random(8));
    }
}