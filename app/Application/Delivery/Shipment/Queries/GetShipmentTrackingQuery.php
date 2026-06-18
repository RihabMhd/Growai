<?php

namespace App\Application\Delivery\Shipment\Queries;

final readonly class GetShipmentTrackingQuery
{
    public function __construct(public int $shipmentId) {}
}
