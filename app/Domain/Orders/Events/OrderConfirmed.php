<?php

namespace App\Domain\Orders\Events;

use App\Domain\Orders\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an order transitions specifically to 'confirmed' status.
 *
 * Fired from: OrderObserver::updated() or a dedicated listener on OrderStatusChanged.
 *
 * Suggested listeners:
 *   - App\Infrastructure\WhatsApp\Templates\OrderConfirmed (already exists)
 *   - TriggerShipmentCreation
 */
class OrderConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Order $order,
    ) {}
}