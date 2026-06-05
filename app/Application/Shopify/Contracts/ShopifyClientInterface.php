<?php

namespace App\Application\Shopify\Contracts;

use App\Domain\Shopify\Models\Shop;

interface ShopifyClientInterface
{
    public function fetchProducts(Shop $shop): array;

    public function fetchOrders(Shop $shop): array;

    public function fetchShop(Shop $shop): array;

    public function updateProduct(Shop $shop, string $productId, array $payload): array;

    public function fetchPrimaryLocationId(Shop $shop): ?string;
    
    public function setInventoryLevel(
        Shop $shop,
        string $inventoryItemId,
        string $locationId,
        int $quantity
    ): void;
}
