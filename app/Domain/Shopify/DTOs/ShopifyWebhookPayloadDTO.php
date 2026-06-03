<?php

namespace App\Domain\Shopify\DTOs;

final readonly class ShopifyWebhookPayloadDTO
{
    public function __construct(
        public string $topic,
        public string $shopDomain,
        public array $payload,
    ) {}
}