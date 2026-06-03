<?php

namespace App\Infrastructure\Orders\Repositories;

use App\Domain\Orders\Models\Order;
use App\Domain\Shipments\Models\Shipment;

class EloquentShipmentRepository implements ShipmentRepositoryInterface
{
    public function createForOrder(Order $order, array $data): Shipment
    {
        return Shipment::create(array_merge(['order_id' => $order->id], $data));
    }

    /**
     * Update the first shipment on an order.
     *
     * Only applies keys present in $data — never overwrites unrelated fields.
     */
    public function updateFirstForOrder(Order $order, array $data): ?Shipment
    {
        $shipment = $order->shipments()->first();

        if (! $shipment || empty($data)) {
            return $shipment;
        }

        $shipment->update($data);

        return $shipment->fresh();
    }
}