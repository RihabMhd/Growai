<?php

namespace App\Application\Dispatch\DispatchOrder;

final class DispatchOrderCommand
{
    public function __construct(
        public readonly int $orderId,
    ) {}
}