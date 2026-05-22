<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
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

                $variants = $this->extractVariants($shopifyProduct);

                $productData = [
                    'shop_id' => $shop->id,
                    'external_product_id' => (string) $shopifyProduct['id'],
                    'title' => $shopifyProduct['title'],
                    'handle' => $shopifyProduct['handle'] ?? null,
                    'vendor' => $shopifyProduct['vendor'] ?? null,
                    'product_type' => $shopifyProduct['product_type'] ?? null,
                    'description' => $shopifyProduct['body_html'] ?? null,
                    'image' => $this->extractImageUrl($shopifyProduct),
                    'images' => $this->extractImageUrls($shopifyProduct),
                    'variants' => $variants,
                    'source_type' => 'shopify',
                    'status' => 'active',
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

    private function fetchShopifyProducts(Shop $shop): array
    {
        $url = "https://{$shop->shopify_domain}/admin/api/2024-01/products.json";

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $shop->access_token,
        ])->get($url);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch Shopify products: ' . $response->body());
        }

        return $response->json('products', []);
    }

    private function extractVariants(array $shopifyProduct): array
    {
        if (empty($shopifyProduct['variants'])) {
            return [];
        }

        $variants = [];
        foreach ($shopifyProduct['variants'] as $variant) {
            $variants[] = [
                'title' => $variant['title'] ?? 'Default',
                'sku' => $variant['sku'] ?? null,
                'price' => floatval($variant['price'] ?? 0),
                'compare_at_price' => $variant['compare_at_price'] ? floatval($variant['compare_at_price']) : null,
                'cost' => $variant['cost'] ? floatval($variant['cost']) : null,
                'stock' => intval($variant['inventory_quantity'] ?? 0),
            ];
        }

        return $variants;
    }

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

    private function extractImageUrls(array $shopifyProduct): array
    {
        $images = [];

        if (!empty($shopifyProduct['images'])) {
            foreach ($shopifyProduct['images'] as $image) {
                if (!empty($image['src'])) {
                    $images[] = ['src' => $image['src']];
                }
            }
        }

        return $images;
    }

    public function testConnection(Shop $shop): bool
    {
        if (!$shop->shopify_domain || !$shop->access_token) {
            return false;
        }

        try {
            $url = "https://{$shop->shopify_domain}/admin/api/2024-01/shop.json";
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $shop->access_token,
            ])->get($url);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Shopify connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
