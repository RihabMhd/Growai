<?php

namespace App\Application\Orders\UseCases\AssignOrder;

final class AssignOrderCommand
{
    public function __construct(
        public readonly int|string $orderId,
        public readonly ?int       $agentId,
        public readonly int        $actorId,
    ) {}
}