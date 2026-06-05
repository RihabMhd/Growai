<?php

namespace App\Infrastructure\Shopify;

use App\Domain\Shopify\Contracts\ShopifyProductClientInterface;
use App\Domain\Products\DTOs\ProductData;

class NullShopifyProductClient implements ShopifyProductClientInterface
{
    public function updateProduct(string $shopifyProductId, ProductData $data): void
    {
        // TODO: implement real Shopify API push
    }
}