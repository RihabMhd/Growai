<?php

namespace App\Application\Shopify\GetShopStatus;

use App\Domain\Shopify\Models\Shop;

final readonly class ShopStatusResult
{
    public function __construct(
        public bool $connected,
        public ?Shop $shop = null,
        public ?int $productCount = null,
        public ?int $orderCount = null,
        public ?string $lastSyncedAt = null
    ) {}
}