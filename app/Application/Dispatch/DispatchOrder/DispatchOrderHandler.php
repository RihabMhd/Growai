<?php

namespace App\Application\Dispatch\DispatchOrder;

use App\Domain\Dispatch\Services\DispatchEngine;
use App\Domain\Orders\Models\Order;
use App\Domain\Teams\Models\Team;
use App\Domain\Teams\Models\User;

final class DispatchOrderHandler
{
    public function __construct(
        private readonly DispatchEngine $engine,
    ) {}

    /**
     * Returns the assigned agent, or null if no eligible agent found.
     * Caller is responsible for persisting assigned_to and audit logging.
     */
    public function handle(DispatchOrderCommand $command): ?User
    {
        $team = Team::first();
        if (! $team || ! $team->dispatch_auto) {
            return null;
        }

        $order = Order::findOrFail($command->orderId);

        return $this->engine->selectAgent($order);
    }
}