<?php

namespace App\Application\Delivery\Shipment\Queries;

final readonly class GetShipmentQuery
{
    public function __construct(public int $shipmentId) {}
}
