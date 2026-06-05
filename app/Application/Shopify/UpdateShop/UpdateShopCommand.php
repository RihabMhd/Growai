<?php

namespace App\Application\Shopify\UpdateShop;

final readonly class UpdateShopCommand
{
    public function __construct(
        public int $shopId,
        public ?string $name,
        public ?string $boutiqueName
    ) {}
}