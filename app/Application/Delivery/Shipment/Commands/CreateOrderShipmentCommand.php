<?php

namespace App\Application\Delivery\Shipment\Commands;

use App\Application\Delivery\Shipment\DTOs\CreateOrderShipmentDTO;

final readonly class CreateOrderShipmentCommand
{
    public function __construct(public CreateOrderShipmentDTO $data) {}
}

