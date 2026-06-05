<?php

namespace App\Domain\Shopify\Contracts;

use App\Domain\Products\DTOs\ProductData;

interface ShopifyProductClientInterface
{
    public function updateProduct(string $shopifyProductId, ProductData $data): void;
}