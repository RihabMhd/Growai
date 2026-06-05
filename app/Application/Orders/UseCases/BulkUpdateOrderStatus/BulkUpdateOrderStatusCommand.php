<?php

namespace App\Application\Orders\UseCases\BulkUpdateOrderStatus;

final class BulkUpdateOrderStatusCommand
{
    public function __construct(
        /** @var int[] */
        public readonly array  $orderIds,
        public readonly string $newStatus,
        public readonly int    $actorId,
    ) {}
}