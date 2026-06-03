<?php

namespace App\Infrastructure\Orders\Services;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderHistory;
use App\Domain\Orders\Services\OrderAuditLogger;

/**
 * Eloquent implementation of the OrderAuditLogger domain contract.
 *
 * Writes directly to the order_histories table using the OrderHistory model.
 *
 * Bound in AppServiceProvider:
 *   $this->app->bind(OrderAuditLogger::class, EloquentOrderAuditLogger::class);
 */
class EloquentOrderAuditLogger implements OrderAuditLogger
{
    public function log(
        Order   $order,
        ?int    $userId,
        string  $actionType,
        ?string $oldValue,
        ?string $newValue,
        string  $description,
    ): void {
        OrderHistory::create([
            'order_id'    => $order->id,
            'user_id'     => $userId,
            'action_type' => $actionType,
            'old_value'   => $oldValue,
            'new_value'   => $newValue,
            'description' => $description,
        ]);
    }
}