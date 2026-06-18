<?php

namespace App\Application\Delivery\Shipment\DTOs;

final readonly class CreateShipmentDTO
{
    public function __construct(
        public int $orderId,
        public int $deliveryCompanyId,
        public ?string $recipientName = null,
        public ?string $recipientPhone = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $region = null,
        public ?string $country = null,
        public ?float $codAmount = null,
        public ?float $weight = null,
        public ?array $dimensions = null,
    ) {}
}
