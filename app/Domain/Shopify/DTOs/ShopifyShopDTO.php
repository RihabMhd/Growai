<?php

namespace App\Domain\Shopify\DTOs;

final readonly class ShopifyShopDTO
{
    public function __construct(
        public string $domain,
        public string $accessToken,
        public bool $isActive = true,
        public ?string $name = null,
    ) {}
}