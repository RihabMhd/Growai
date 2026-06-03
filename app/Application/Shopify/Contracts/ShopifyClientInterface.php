<?php

namespace App\Application\Shopify\Contracts;

use App\Domain\Shopify\Models\Shop;

interface ShopifyClientInterface
{
    public function fetchProducts(Shop $shop): array;

    public function fetchOrders(Shop $shop): array;

    public function fetchShop(Shop $shop): array;
}