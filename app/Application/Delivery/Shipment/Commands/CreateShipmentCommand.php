<?php

namespace App\Application\Delivery\Shipment\Commands;

use App\Application\Delivery\Shipment\DTOs\CreateShipmentDTO;

final readonly class CreateShipmentCommand
{
    public function __construct(public CreateShipmentDTO $data) {}
}
