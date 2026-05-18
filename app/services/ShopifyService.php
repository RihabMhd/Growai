<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    /**
     * Fetch all products from Shopify store and sync with local database.
     */
    public function syncProducts(Shop $shop): array
    {
        if (!$shop->shopify_domain || !$shop->access_token) {
            throw new \Exception('Shop not properly configured for Shopify integration');
        }

        try {
            $products = $this->fetchShopifyProducts($shop);
            $imported = 0;
            $updated = 0;

            foreach ($products as $shopifyProduct) {
                $existingProduct = Product::where('shop_id', $shop->id)
                    ->where('external_product_id', (string) $shopifyProduct['id'])
                    ->first();

                $productData = [
                    'shop_id' => $shop->id,
                    'external_product_id' => (string) $shopifyProduct['id'],
                    'name' => $shopifyProduct['title'],
                    'sku' => $shopifyProduct['variants'][0]['sku'] ?? null,
                    'price' => (float) ($shopifyProduct['variants'][0]['price'] ?? 0),
                    'stock' => (int) ($shopifyProduct['variants'][0]['inventory_quantity'] ?? 0),
                    'image' => $this->extractImageUrl($shopifyProduct),
                    'source_type' => 'shopify',
                ];

                if ($existingProduct) {
                    $existingProduct->update($productData);
                    $updated++;
                } else {
                    Product::create($productData);
                    $imported++;
                }
            }

            Log::info('Shopify products synced', [
                'shop_id' => $shop->id,
                'imported' => $imported,
                'updated' => $updated,
            ]);

            return [
                'success' => true,
                'imported' => $imported,
                'updated' => $updated,
                'total' => count($products),
            ];
        } catch (\Exception $e) {
            Log::error('Shopify sync failed', [
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Fetch products from Shopify API.
     */
    private function fetchShopifyProducts(Shop $shop): array
    {
        $url = "https://{$shop->shopify_domain}/admin/api/2024-01/products.json";

        $response = Http::withToken($shop->access_token)
            ->get($url, [
                'limit' => 250,
                'status' => 'active',
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch Shopify products: ' . $response->body());
        }

        return $response->json('products', []);
    }

    /**
     * Extract main product image URL from Shopify product.
     */
    private function extractImageUrl(array $shopifyProduct): ?string
    {
        if (!empty($shopifyProduct['image']['src'])) {
            return $shopifyProduct['image']['src'];
        }

        if (!empty($shopifyProduct['images'][0]['src'])) {
            return $shopifyProduct['images'][0]['src'];
        }

        return null;
    }

    /**
     * Test Shopify connection.
     */
    public function testConnection(Shop $shop): bool
    {
        if (!$shop->shopify_domain || !$shop->access_token) {
            return false;
        }

        try {
            $url = "https://{$shop->shopify_domain}/admin/api/2024-01/shop.json";
            $response = Http::withToken($shop->access_token)->get($url);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Shopify connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
