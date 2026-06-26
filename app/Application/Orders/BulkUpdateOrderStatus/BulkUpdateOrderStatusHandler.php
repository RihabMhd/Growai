<?php

namespace App\Application\Orders\BulkUpdateOrderStatus;

use App\Infrastructure\Orders\Repositories\OrderRepositoryInterface;

class BulkUpdateOrderStatusHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(BulkUpdateOrderStatusCommand $command): int
    {
        $changed = $this->orders->bulkUpdateStatus($command->orderIds, $command->newStatus);

        return $changed->count();
    }
}