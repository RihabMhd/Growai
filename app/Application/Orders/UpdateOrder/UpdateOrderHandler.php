<?php

namespace App\Application\Orders\UpdateOrder;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Services\OrderAuditLogger;
use App\Domain\Orders\Services\OrderItemsReplacer;
use App\Domain\Orders\States\OrderStateMachine;
use App\Domain\Orders\Services\OrderStatusValidator;
use App\Domain\Orders\Repositories\ClientRepositoryInterface;
use App\Infrastructure\Orders\Repositories\OrderRepositoryInterface;
use App\Infrastructure\Orders\Repositories\ShipmentRepositoryInterface;
use Illuminate\Support\Facades\DB;

class UpdateOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface    $orders,
        private readonly ClientRepositoryInterface   $clients,
        private readonly ShipmentRepositoryInterface $shipments,
        private readonly OrderItemsReplacer          $itemsReplacer,
        private readonly OrderAuditLogger            $auditLogger,
        private readonly OrderStatusValidator        $statusValidator,
    ) {}

    public function handle(UpdateOrderCommand $command): Order
    {
        $order = $this->orders->findWithRelations($command->orderId);

        DB::transaction(function () use ($command, $order) {

            if ($order->client && $this->hasClientData($command)) {
                $clientData = [];
                if (in_array('customer_name', $command->providedFields)) $clientData['name'] = $command->customerName;
                if (in_array('customer_phone', $command->providedFields)) $clientData['phone'] = $command->customerPhone;
                if (in_array('customer_email', $command->providedFields)) $clientData['email'] = $command->customerEmail;
                if (in_array('city', $command->providedFields)) $clientData['city'] = $command->city;
                if (in_array('province', $command->providedFields)) $clientData['province'] = $command->province;
                if (in_array('street', $command->providedFields)) $clientData['address'] = $command->street;
                
                if (!empty($clientData)) {
                    $this->clients->update($order->client, $clientData);
                }
            }

            $orderFields = [];

            if ($command->items !== null) {
                $subtotal = $this->itemsReplacer->replace($order, $command->items);
                $shipping = $command->shippingPrice ?? $order->shipping_price;
                $orderFields['total_price'] = $subtotal + $shipping;
            }

            if ($this->hasAddressData($command)) {
                $shipmentData = [];
                if (in_array('customer_name', $command->providedFields)) $shipmentData['recipient_name'] = $command->customerName;
                if (in_array('customer_phone', $command->providedFields)) $shipmentData['recipient_phone'] = $command->customerPhone;
                if (in_array('city', $command->providedFields)) $shipmentData['city'] = $command->city;
                if (in_array('province', $command->providedFields)) $shipmentData['region'] = $command->province;
                
                if (in_array('street', $command->providedFields) || in_array('city', $command->providedFields) || in_array('province', $command->providedFields)) {
                    $shipmentData['address'] = implode(', ', array_filter([
                        $command->street,
                        $command->city,
                        $command->province,
                    ]));
                }

                if (!empty($shipmentData)) {
                    $this->shipments->updateFirstForOrder($order, $shipmentData);
                }
            }

            if (in_array('financial_status', $command->providedFields)) $orderFields['financial_status'] = $command->financialStatus;
            if (in_array('notes', $command->providedFields)) $orderFields['notes'] = $command->notes;
            if (in_array('shipping_price', $command->providedFields)) $orderFields['shipping_price'] = $command->shippingPrice;
            if (in_array('customer_name', $command->providedFields)) $orderFields['customer_name'] = $command->customerName;
            if (in_array('customer_phone', $command->providedFields)) $orderFields['customer_phone'] = $command->customerPhone;
            if (in_array('customer_email', $command->providedFields)) $orderFields['customer_email'] = $command->customerEmail;
            if (in_array('province', $command->providedFields)) $orderFields['province'] = $command->province;
            if (in_array('city', $command->providedFields)) $orderFields['city'] = $command->city;
            if (in_array('street', $command->providedFields)) $orderFields['street'] = $command->street;
            
            if ($this->hasAddressData($command)) {
                $shippingAddress = $order->shipping_address ?? [];
                if (in_array('city', $command->providedFields)) $shippingAddress['city'] = $command->city;
                if (in_array('province', $command->providedFields)) $shippingAddress['province'] = $command->province;
                if (in_array('street', $command->providedFields)) $shippingAddress['address1'] = $command->street;
                $orderFields['shipping_address'] = $shippingAddress;
            }

            if ($command->status !== null && $command->status !== $order->status) {
                $this->statusValidator->assertExists($command->status);

                $machine = new OrderStateMachine($order);
                $machine->transitionTo($command->status);

                $orderFields['status'] = $command->status;
            }

            if (! empty($orderFields)) {
                $this->orders->update($order, $orderFields);
            }
            
            if ($this->hasClientData($command)) {
                $this->auditLogger->log(
                    order:       $order,
                    userId:      $command->actorId,
                    actionType:  'customer',
                    oldValue:    null,
                    newValue:    null,
                    description: "Informations client mises à jour"
                );
            }
            
            if ($command->items !== null) {
                $this->auditLogger->log(
                    order:       $order,
                    userId:      $command->actorId,
                    actionType:  'items',
                    oldValue:    null,
                    newValue:    null,
                    description: "Produits de la commande mis à jour"
                );
            }

            if (in_array('financial_status', $command->providedFields) && $command->financialStatus !== null) {
                $oldFinancialStatus = $order->getOriginal('financial_status');
                if ($oldFinancialStatus !== $command->financialStatus) {
                    $this->auditLogger->log(
                        order:       $order,
                        userId:      $command->actorId,
                        actionType:  'payment_status_changed',
                        oldValue:    $oldFinancialStatus,
                        newValue:    $command->financialStatus,
                        description: "Statut de paiement modifié de '{$oldFinancialStatus}' à '{$command->financialStatus}'."
                    );
                }
            }

            if (in_array('notes', $command->providedFields) && $command->notes !== null) {
                $oldNotes = $order->getOriginal('notes');
                if ($oldNotes !== $command->notes) {
                    $this->auditLogger->log(
                        order:       $order,
                        userId:      $command->actorId,
                        actionType:  'note_added',
                        oldValue:    $oldNotes,
                        newValue:    $command->notes,
                        description: "Notes de la commande mises à jour."
                    );
                }
            }
        });

        return $this->orders->findWithRelations($command->orderId);
    }

    private function hasClientData(UpdateOrderCommand $command): bool
    {
        return in_array('customer_name', $command->providedFields)
            || in_array('customer_phone', $command->providedFields)
            || in_array('customer_email', $command->providedFields)
            || in_array('city', $command->providedFields)
            || in_array('province', $command->providedFields)
            || in_array('street', $command->providedFields);
    }

    private function hasAddressData(UpdateOrderCommand $command): bool
    {
        return in_array('city', $command->providedFields)
            || in_array('province', $command->providedFields)
            || in_array('street', $command->providedFields);
    }
}