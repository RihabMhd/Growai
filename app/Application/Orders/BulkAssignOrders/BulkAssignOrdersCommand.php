<?php

namespace App\Application\Orders\BulkAssignOrders;

final class BulkAssignOrdersCommand
{
    public function __construct(
        public readonly array $orderIds,
        public readonly ?int  $agentId,
        public readonly int   $actorId,
    ) {}
}