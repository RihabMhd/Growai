<?php

namespace App\Domain\Delivery\Shipment\Repositories;

use App\Domain\Delivery\Shipment\Entities\ShipmentHistory;

interface ShipmentHistoryRepositoryInterface
{
    public function record(ShipmentHistory $history): ShipmentHistory;

    /** @return ShipmentHistory[] */
    public function findByShipmentId(int $shipmentId): array;
}
