<?php

namespace App\Application\Shopify\ListShops;

use App\Application\Shopify\Contracts\ShopRepositoryInterface;

final readonly class ListShopsHandler
{
    public function __construct(
        private ShopRepositoryInterface $shops
    ) {}

    public function handle(
        ListShopsQuery $query
    )
    {
        return $this->shops->activeShops();
    }
}