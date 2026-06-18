<?php

namespace App\Application\Delivery\Shipment\Commands;

final readonly class CancelShipmentCommand
{
    public function __construct(public int $shipmentId) {}
}
