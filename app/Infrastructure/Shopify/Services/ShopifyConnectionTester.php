<?php

namespace App\Infrastructure\Shopify\Services;

use App\Domain\Shopify\Models\Shop;
use App\Infrastructure\Shopify\Clients\ShopifyClient;
use App\Domain\Shopify\Exceptions\ShopifyApiException;
use Illuminate\Support\Facades\Log;

final readonly class ShopifyConnectionTester
{
    public function __construct(
        private ShopifyClient $client
    ) {}

    public function test(Shop $shop): bool
    {
        try {
            $data = $this->client->fetchShop($shop);

            return !empty($data);
        } catch (ShopifyApiException $e) {
            Log::warning('Shopify connection test failed', [
                'shop_id' => $shop->id,
                'error'   => $e->getMessage(),
            ]);

            return false;
        }
    }
}