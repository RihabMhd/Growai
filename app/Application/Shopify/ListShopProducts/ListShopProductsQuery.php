<?php

namespace App\Application\Shopify\ListShopProducts;

final readonly class ListShopProductsQuery
{
    public function __construct(
        public int $shopId,
        public ?string $search,
        public int $perPage = 24
    ) {}
}