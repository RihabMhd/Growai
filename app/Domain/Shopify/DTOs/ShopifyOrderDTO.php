<?php

namespace App\Domain\Shopify\DTOs;

final readonly class ShopifyOrderDTO
{
    public function __construct(
        public string $id,
        public ?string $orderNumber,
        public ?string $customerEmail,
        public ?string $customerName,
        public ?string $customerPhone,
        public string $status,
        public ?string $paymentStatus,
        public ?string $fulfillmentStatus,
        public float $totalPrice,
        public string $currency,
        public array $items,
        public array $payload = [],
    ) {}
}