<?php

namespace App\Infrastructure\Shopify\Services;

use App\Domain\Shopify\Models\Shop;
use App\Infrastructure\Shopify\Clients\ShopifyClient;

final readonly class ShopifyConnectionTester
{
    public function __construct(
        private ShopifyClient $client
    ) {}

    public function test(
        Shop $shop
    ): bool {

        try {
            $this->client->fetchShop(
                $shop
            );

            return true;

        } catch (\Throwable) {

            return false;
        }
    }
}