<?php

namespace App\Application\Commissions\GenerateCommission;

final class GenerateCommissionCommand
{
    public function __construct(
        public readonly int    $orderId,
        public readonly string $newStatus,
    ) {}
}