<?php

namespace App\Listeners;

use App\Application\Commissions\GenerateCommission\GenerateCommissionCommand;
use App\Application\Commissions\GenerateCommission\GenerateCommissionHandler;
use App\Application\Commissions\ReverseCommission\ReverseCommissionCommand;
use App\Application\Commissions\ReverseCommission\ReverseCommissionHandler;
use App\Domain\Orders\Events\OrderStatusChanged;
use App\Domain\Orders\Services\OrderAuditLogger;

class ProcessCommission
{
    public function __construct(
        private readonly GenerateCommissionHandler $generateHandler,
        private readonly ReverseCommissionHandler  $reverseHandler,
        private readonly OrderAuditLogger          $auditLogger,
    ) {}

    public function handle(OrderStatusChanged $event): void
    {
        $order     = $event->order;
        $newStatus = $event->newStatus;

        // reversal on cancellation
        if ($newStatus === 'annule') {
            $reversed = $this->reverseHandler->handle(
                new ReverseCommissionCommand($order->id)
            );

            if ($reversed > 0.00) {
                $currency = $order->currency ?? 'MAD';
                $agent    = $order->assignedAgent;

                $this->auditLogger->log(
                    order:       $order,
                    userId:      $agent?->id,
                    actionType:  'commission',
                    oldValue:    (string) $reversed,
                    newValue:    '0',
                    description: sprintf(
                        "Commission de %s %s annulée suite à l'annulation de la commande. Agent: %s.",
                        number_format($reversed, 2, '.', ''),
                        $currency,
                        $agent?->name ?? 'inconnu',
                    ),
                );
            }

            return;
        }

        // generation on trigger status
        $amount = $this->generateHandler->handle(
            new GenerateCommissionCommand(
                orderId:   $order->id,
                newStatus: $newStatus,
            )
        );

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