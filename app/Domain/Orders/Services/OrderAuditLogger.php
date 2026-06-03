<?php

namespace App\Domain\Orders\Services;

use App\Domain\Orders\Models\Order;

/**
 * Domain contract for writing audit history entries on an order.
 *
 * The concrete implementation lives in:
 *   App\Infrastructure\Orders\Services\EloquentOrderAuditLogger
 *
 * Bind in AppServiceProvider:
 *   $this->app->bind(OrderAuditLogger::class, EloquentOrderAuditLogger::class);
 */
interface OrderAuditLogger
{
    /**
     * Write a history entry for the given order.
     *
     * @param  Order       $order       The order being audited.
     * @param  int|null    $userId      The user who triggered the action (null = system).
     * @param  string      $actionType  e.g. 'status', 'assigned', 'commission'.
     * @param  string|null $oldValue    Previous value (null for creation events).
     * @param  string|null $newValue    New value.
     * @param  string      $description Human-readable French description.
     */
    public function log(
        Order   $order,
        ?int    $userId,
        string  $actionType,
        ?string $oldValue,
        ?string $newValue,
        string  $description,
    ): void;
}