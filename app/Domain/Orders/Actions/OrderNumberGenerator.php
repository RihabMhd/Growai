<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Teams\Models\Team;
use Illuminate\Support\Str;

class OrderNumberGenerator
{
    public function generate(): string
    {
        $team   = Team::first();
        $prefix = ($team?->order_prefix) ? $team->order_prefix : 'ORD';

        return $prefix . '-' . strtoupper(Str::random(8));
    }
}