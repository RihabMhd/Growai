<?php

namespace App\Infrastructure\Orders\Repositories;

use App\Infrastructure\Delivery\Persistence\Eloquent\Models\ShipmentModel;
use App\Domain\Orders\Models\Order;

/**
 * Order-module adapter for shipment persistence.
 */
interface ShipmentRepositoryInterface
{
    public function createForOrder(Order $order, array $data): ShipmentModel;

    public function updateFirstForOrder(Order $order, array $data): ?ShipmentModel;
}
