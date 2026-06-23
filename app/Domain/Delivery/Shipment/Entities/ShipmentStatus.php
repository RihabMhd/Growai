<?php

namespace App\Domain\Delivery\Shipment\Entities;

final readonly class ShipmentStatus
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public string $color,
        public int $position,
        public bool $isFinal,
    ) {}
}
