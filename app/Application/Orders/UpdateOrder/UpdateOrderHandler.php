<?php

namespace App\Application\Orders\UpdateOrder;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Services\OrderAuditLogger;
use App\Domain\Orders\Services\OrderItemsReplacer;
use App\Domain\Orders\States\OrderStateMachine;
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
    ) {}

    public function handle(UpdateOrderCommand $command): Order
    {
        $order = $this->orders->findWithRelations($command->orderId);

        DB::transaction(function () use ($command, $order) {

            // 1. Update client if customer fields are provided
            if ($order->client && $this->hasClientData($command)) {
                $this->clients->update($order->client, array_filter([
                    'name'    => $command->customerName,
                    'phone'   => $command->customerPhone,
                    'email'   => $command->customerEmail,
                    'city'    => $command->city,
                    'address' => $command->street,
                ], fn ($v) => $v !== null));
            }

            // 2. Replace items if a new items array was provided
            if ($command->items !== null) {
                $subtotal = $this->itemsReplacer->replace($order, $command->items);
                $shipping = $command->shippingPrice ?? $order->shipping_price;
                $order->update(['total_price' => $subtotal + $shipping]);
            }

            // 3. Update shipment address if address data changed
            if ($this->hasAddressData($command)) {
                $this->shipments->updateFirstForOrder($order, array_filter([
                    'recipient_name'  => $command->customerName,
                    'recipient_phone' => $command->customerPhone,
                    'address'         => implode(', ', array_filter([
                        $command->street,
                        $command->city,
                        $command->province,
                    ])),
                    'city'            => $command->city,
                    'region'          => $command->province,
                ], fn ($v) => $v !== null));
            }

            // 4. Apply status transition via state machine
            $orderFields = array_filter([
                'financial_status' => $command->financialStatus,
                'notes'            => $command->notes,
                'shipping_price'   => $command->shippingPrice,
            ], fn ($v) => $v !== null);

            if ($command->status !== null && $command->status !== $order->status) {
                $machine = new OrderStateMachine($order);
                $machine->transitionTo($command->status);
                $orderFields['status'] = $command->status;
            }

            if (! empty($orderFields)) {
                $this->orders->update($order, $orderFields);
            }
        });

        return $this->orders->findWithRelations($command->orderId);
    }

    private function hasClientData(UpdateOrderCommand $command): bool
    {
        return $command->customerName  !== null
            || $command->customerPhone !== null
            || $command->customerEmail !== null;
    }

    private function hasAddressData(UpdateOrderCommand $command): bool
    {
        return $command->city     !== null
            || $command->province !== null
            || $command->street   !== null;
    }
}