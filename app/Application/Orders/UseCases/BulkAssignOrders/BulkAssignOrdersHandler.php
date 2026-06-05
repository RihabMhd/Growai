<?php

namespace App\Application\Orders\UseCases\BulkAssignOrders;

use App\Domain\Orders\Services\OrderAuditLogger;
use App\Infrastructure\Orders\Repositories\OrderRepositoryInterface;
use App\Infrastructure\Orders\Repositories\UserRepositoryInterface;

class BulkAssignOrdersHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly UserRepositoryInterface  $users,
        private readonly OrderAuditLogger         $auditLogger,
    ) {}

    public function handle(BulkAssignOrdersCommand $command): int
    {
        $assigned  = $this->orders->bulkAssign($command->orderIds, $command->agentId);
        $newAgent  = $command->agentId ? $this->users->find($command->agentId) : null;
        $agentName = $newAgent?->name ?? 'unassigned';

        foreach ($assigned as $order) {
            $oldAgentName = $order->assignedAgent?->name ?? 'unassigned';

            $this->auditLogger->log(
                order:       $order,
                userId:      $command->actorId,
                actionType:  'assigned',
                oldValue:    $oldAgentName,
                newValue:    $agentName,
                description: "Commande réassignée en masse à '{$agentName}'.",
            );
        }

        return $assigned->count();
    }
}