<?php

namespace App\Application\Shopify\ListShopOrders;

final readonly class ListShopOrdersQuery
{
    public function __construct(
        public int $shopId,
        public ?string $status,
        public ?string $search,
        public int $perPage = 20
    ) {}
}