<?php

namespace App\Observers;

use App\Domain\Orders\Events\OrderStatusChanged;
use App\Domain\Orders\Models\Order;

/**
 * Thin observer — only responsible for firing domain events.
 *
 * Auto-dispatch  → handled by AutoDispatchService called from CreateOrderHandler
 * History log    → handled by OrderAuditLogger called from handlers / listeners
 * Commission     → handled by ProcessCommission listener via OrderStatusChanged
 * WhatsApp       → handled by SendWhatsappNotification listener via OrderStatusChanged
 */
class OrderObserver
{
    /**
     * The created hook is intentionally empty.
     * Auto-dispatch is triggered explicitly in CreateOrderHandler
     * after the order is persisted, keeping causality traceable.
     */
    public function created(Order $order): void
    {
        //
    }

    /**
     * Fire OrderStatusChanged so all listeners react independently.
     * Nothing else belongs here.
     */
    public function updated(Order $order): void
    {
        logger()->info('ORDER UPDATED', [
            'order_id' => $order->id,
            'dirty' => $order->getDirty(),
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
        ]);

        if (! $order->isDirty('status')) {
            return;
        }

        OrderStatusChanged::dispatch(
            $order,
            $order->getOriginal('status') ?? 'none',
            $order->status,
        );
    }
}
