<?php

namespace App\Application\Orders\AssignOrder;

final class AssignOrderCommand
{
    public function __construct(
        public readonly int|string $orderId,
        public readonly ?int       $agentId,
        public readonly int        $actorId,
    ) {}
}