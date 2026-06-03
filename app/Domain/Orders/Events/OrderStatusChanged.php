<?php

namespace App\Domain\Orders\Events;

use App\Domain\Orders\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an order's status changes.
 *
 * Listeners (register in EventServiceProvider):
 *   - LogOrderHistory       → writes audit trail
 *   - ProcessCommission     → credits agent wallet if trigger matches
 *   - SendWhatsappNotification → sends WhatsApp message to client
 */
class OrderStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Order  $order,
        public readonly string $oldStatus,
        public readonly string $newStatus,
    ) {}
}