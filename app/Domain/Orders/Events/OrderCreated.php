<?php

namespace App\Domain\Orders\Events;

use App\Domain\Orders\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after a new order is fully persisted (items, shipment, source, history).
 *
 * Fired from: CreateOrderHandler (after transaction commits + auto-dispatch runs)
 *
 * Suggested listeners (register in EventServiceProvider):
 *   - SendOrderCreatedNotification
 *   - TriggerRecoveryRules (for abandoned orders)
 */
class OrderCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Order $order,
    ) {}
}