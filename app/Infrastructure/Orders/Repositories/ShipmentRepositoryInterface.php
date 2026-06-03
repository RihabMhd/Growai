<?php

namespace App\Infrastructure\Orders\Repositories;

use App\Domain\Shipments\Models\Shipment;
use App\Domain\Orders\Models\Order;

/**
 * Domain contract for shipment persistence.
 *
 * Bind in AppServiceProvider:
 *   $this->app->bind(ShipmentRepositoryInterface::class, EloquentShipmentRepository::class);
 */
interface ShipmentRepositoryInterface
{
    /**
     * Create a shipment record for the given order.
     */
    public function createForOrder(Order $order, array $data): Shipment;

    /**
     * Update the first shipment on an order with the given data.
     * Returns the updated shipment, or null if none exists.
     */
    public function updateFirstForOrder(Order $order, array $data): ?Shipment;
}