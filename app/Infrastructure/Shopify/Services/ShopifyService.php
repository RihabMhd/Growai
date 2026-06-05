<?php

namespace App\Infrastructure\Shopify\Services;

use App\Domain\Shopify\Models\Shop;
use App\Infrastructure\Shopify\Clients\ShopifyClient;

final readonly class ShopifyService
{
    public function __construct(
        private ShopifyClient $client,
        private ShopifyConnectionTester $tester,
        private ShopifyProductImporter $importer,
    ) {}

    public function syncProducts(Shop $shop): array
    {
        if (!$shop->shopify_location_id) {
            $locationId = $this->client->fetchPrimaryLocationId($shop);

            if ($locationId) {
                $shop->update([
                    'shopify_location_id' => $locationId,
                ]);
            }
        }

        $products = $this->client->fetchProducts($shop);

        return $this->importer->sync($shop, $products);
    }
    public function testConnection(
        Shop $shop
    ): bool {

        return $this->tester
            ->test($shop);
    }
}
