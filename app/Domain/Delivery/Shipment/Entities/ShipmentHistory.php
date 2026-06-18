<?php

namespace App\Domain\Delivery\Shipment\Entities;

final readonly class ShipmentHistory
{
    public function __construct(
        public ?int $id,
        public int $shipmentId,
        public ?string $oldStatus,
        public string $newStatus,
        public string $source,
        public ?string $description = null,
        public ?array $payload = null,
        public ?\DateTimeImmutable $createdAt = null,
    ) {}
}
