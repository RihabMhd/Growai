<?php

namespace App\Application\Shopify\SyncShopProducts;

final readonly class SyncShopProductsCommand
{
    public function __construct(
        public int $shopId
    ) {}
}