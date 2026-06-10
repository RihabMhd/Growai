<?php

namespace App\Application\Shopify\SyncShopOrders;

final readonly class SyncShopOrdersCommand
{
    public function __construct(
        public readonly int $shopId,
    ) {}
}