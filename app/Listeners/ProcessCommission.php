<?php

namespace App\Listeners;

use App\Domain\Orders\Events\OrderStatusChanged;
use App\Domain\Orders\Services\CommissionService;
use App\Domain\Orders\Services\OrderAuditLogger;

/**
 * Listens for OrderStatusChanged and processes agent commission if due.
 *
 * Registered in EventServiceProvider:
 *   OrderStatusChanged::class => [ProcessCommission::class]
 */
class ProcessCommission
{
    public function __construct(
        private readonly CommissionService $commissionService,
        private readonly OrderAuditLogger  $auditLogger,
    ) {}

    public function handle(OrderStatusChanged $event): void
    {
        $order     = $event->order;
        $newStatus = $event->newStatus;

        $amount = $this->commissionService->processForOrder($order, $newStatus);

        if ($amount > 0.00) {
            $currency = $order->currency ?? 'MAD';
            $agent    = $order->assignedAgent;

            $this->auditLogger->log(
                order:       $order,
                userId:      $agent?->id,
                actionType:  'commission',
                oldValue:    '0',
                newValue:    (string) $amount,
                description: sprintf(
                    "Commission de %s %s créditée automatiquement au portefeuille de l'agent %s.",
                    number_format($amount, 2, '.', ''),
                    $currency,
                    $agent?->name ?? 'inconnu',
                ),
            );
        }
    }
}