<?php

namespace App\Application\Delivery\Shipment\Commands;

final readonly class SyncShipmentStatusCommand
{
    public function __construct(
        public int $shipmentId,
        public string $source = 'carrier_sync',
    ) {}
}
