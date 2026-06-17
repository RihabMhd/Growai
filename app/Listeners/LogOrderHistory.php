<?php

namespace App\Listeners;

use App\Domain\Orders\Events\OrderStatusChanged;
use App\Domain\Orders\Services\OrderAuditLogger;

/**
 * Listens for OrderStatusChanged and writes an audit history entry.
 *
 * Replaces the inline OrderHistory::create() calls that were scattered
 * across OrderObserver::updated() and the controller methods.
 *
 * Registered in EventServiceProvider:
 *   OrderStatusChanged::class => [
 *       LogOrderHistory::class,
 *       ProcessCommission::class,
 *       SendWhatsappNotification::class,
 *   ]
 */
class LogOrderHistory
{
    public function __construct(
        private readonly OrderAuditLogger $auditLogger,
    ) {}

    public function handle(OrderStatusChanged $event): void
    {
        logger()->info('LOG_ORDER_HISTORY_CALLED', [
            'order_id' => $event->order->id,
            'old' => $event->oldStatus,
            'new' => $event->newStatus,
        ]);
        $this->auditLogger->log(
            order: $event->order,
            userId: auth()->id() ?? $event->order->assigned_to,
            actionType: 'status',
            oldValue: $event->oldStatus,
            newValue: $event->newStatus,
            description: "Statut de la commande modifié de '{$event->oldStatus}' à '{$event->newStatus}'.",
        );
    }
}
