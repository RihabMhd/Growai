<?php

namespace App\Application\Orders\BulkUpdateOrderStatus;

use App\Domain\Orders\Services\OrderAuditLogger;
use App\Infrastructure\Orders\Repositories\OrderRepositoryInterface;

class BulkUpdateOrderStatusHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderAuditLogger         $auditLogger,
    ) {}

    public function handle(BulkUpdateOrderStatusCommand $command): int
    {
        $changed = $this->orders->bulkUpdateStatus($command->orderIds, $command->newStatus);

        foreach ($changed as $order) {
            $this->auditLogger->log(
                order:       $order,
                userId:      $command->actorId,
                actionType:  'status_changed',
                oldValue:    $order->getOriginal('status'),
                newValue:    $command->newStatus,
                description: "Statut mis à jour en masse vers '{$command->newStatus}'.",
            );
        }

        return $changed->count();
    }
}