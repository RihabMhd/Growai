<?php

namespace App\Application\Orders\AssignOrder;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Services\OrderAuditLogger;
use App\Infrastructure\Orders\Repositories\OrderRepositoryInterface;
use App\Infrastructure\Orders\Repositories\UserRepositoryInterface;

class AssignOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly UserRepositoryInterface  $users,
        private readonly OrderAuditLogger         $auditLogger,
    ) {}

    public function handle(AssignOrderCommand $command): Order
    {
        $order = $this->orders->findWithRelations($command->orderId);

        $oldAgentName = $order->assignedAgent?->name ?? 'unassigned';

        $this->orders->assignAgent($order, $command->agentId);

        $newAgent     = $command->agentId ? $this->users->find($command->agentId) : null;
        $newAgentName = $newAgent?->name ?? 'unassigned';

        $this->auditLogger->log(
            order:       $order,
            userId:      $command->actorId,
            actionType:  'assigned',
            oldValue:    $oldAgentName,
            newValue:    $newAgentName,
            description: "Commande réassignée de '{$oldAgentName}' à '{$newAgentName}'.",
        );

        return $this->orders->findWithRelations($command->orderId);
    }
}