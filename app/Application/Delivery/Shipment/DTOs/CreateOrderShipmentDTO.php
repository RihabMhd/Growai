<?php

namespace App\Application\Delivery\Shipment\DTOs;

final readonly class CreateOrderShipmentDTO
{
    public function __construct(
        public int $orderId,
        public int $deliveryCompanyId,
        public string $city,
        public string $clientName,
        public string $phone,
        public string $address,
        public float $total,
        public ?string $note = null,
        // AMEEX-specific
        public ?string $apiId = null,
        public ?string $deliveryType = null,
        public ?bool $openable = null,
        public ?bool $testProduct = null,
        public ?bool $fragile = null,
        public ?string $product = null,
        public ?bool $exchange = null,
    ) {}
}

