<?php

namespace App\Application\Shopify\ConnectShop;

final readonly class ConnectShopCommand
{
    public function __construct(
        public string $shop,
        public string $code,
    ) {}
}