<?php

namespace App\Application\Commissions\ReverseCommission;

final class ReverseCommissionCommand
{
    public function __construct(
        public readonly int $orderId,
    ) {}
}