<?php

namespace App\Application\Orders\UseCases\BulkAssignOrders;

final class BulkAssignOrdersCommand
{
    public function __construct(
        /** @var int[] */
        public readonly array $orderIds,
        public readonly ?int  $agentId,
        public readonly int   $actorId,
    ) {}
}