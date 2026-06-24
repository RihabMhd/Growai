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

    // caller must persist assignment and audit log
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