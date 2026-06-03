<?php

namespace App\Application\Shopify\DisconnectShop;

final readonly class DisconnectShopCommand
{
    public function __construct(
        public int $shopId
    ) {}
}