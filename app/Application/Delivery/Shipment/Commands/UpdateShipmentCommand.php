<?php

namespace App\Application\Delivery\Shipment\Commands;

final readonly class UpdateShipmentCommand
{
    public function __construct(
        public int $shipmentId,
        public ?string $statusSlug = null,
        public ?string $deliveryNotes = null,
        public string $source = 'manual',
    ) {}
}
