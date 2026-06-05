<?php

namespace App\Infrastructure\Shopify\Clients;

use App\Application\Shopify\Contracts\ShopifyClientInterface;
use App\Domain\Shopify\Exceptions\ShopifyApiException;
use App\Domain\Shopify\Models\Shop;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ShopifyClient implements ShopifyClientInterface
{
    private const API_VERSION = '2025-01';

    public function fetchPrimaryLocationId(Shop $shop): ?string
    {
        $response = $this->request($shop, 'GET', '/locations.json');

        return $response['locations'][0]['id'] ?? null;
    }

    public function setInventoryLevel(
        Shop $shop,
        string $inventoryItemId,
        string $locationId,
        int $quantity
    ): void {
        $this->request(
            $shop,
            'POST',
            '/inventory_levels/set.json',
            [
                'location_id' => $locationId,
                'inventory_item_id' => $inventoryItemId,
                'available' => $quantity,
            ]
        );
    }

    public function fetchProducts(Shop $shop): array
    {
        $response = $this->request(
            $shop,
            'GET',
            '/products.json'
        );

        return $response['products'] ?? [];
    }

    public function fetchOrders(Shop $shop): array
    {
        $response = $this->request(
            $shop,
            'GET',
            '/orders.json'
        );

        return $response['orders'] ?? [];
    }

    public function fetchShop(Shop $shop): array
    {
        $response = $this->request(
            $shop,
            'GET',
            '/shop.json'
        );

        return $response['shop'] ?? [];
    }

    public function fetchProduct(
        Shop $shop,
        string $productId
    ): array {
        $response = $this->request(
            $shop,
            'GET',
            "/products/{$productId}.json"
        );

        return $response['product'] ?? [];
    }

    public function fetchOrder(
        Shop $shop,
        string $orderId
    ): array {
        $response = $this->request(
            $shop,
            'GET',
            "/orders/{$orderId}.json"
        );

        return $response['order'] ?? [];
    }

    private function request(
        Shop $shop,
        string $method,
        string $endpoint,
        array $payload = []
    ): array {
        if (
            empty($shop->shopify_domain) ||
            empty($shop->access_token)
        ) {
            throw new ShopifyApiException(
                'Shop is not properly connected to Shopify.'
            );
        }

        $url = sprintf(
            'https://%s/admin/api/%s%s',
            $shop->shopify_domain,
            self::API_VERSION,
            $endpoint
        );

        $client = Http::withHeaders([
            'X-Shopify-Access-Token' => $shop->access_token,
            'Accept' => 'application/json',
        ]);

        $response = match ($method) {
            'GET' => $client->get($url, $payload),
            'POST' => $client->post($url, $payload),
            'PUT' => $client->put($url, $payload),
            'DELETE' => $client->delete($url),
            default => $client->send($method, $url, [
                'json' => $payload,
            ]),
        };

        if ($response->failed()) {

            Log::error('Shopify Response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $url,
            ]);

            throw new ShopifyApiException(
                $response->body()
            );
        }

        return $response->json();
    }

    public function updateProduct(Shop $shop, string $productId, array $payload): array
    {
        $response = $this->request(
            $shop,
            'PUT',
            "/products/{$productId}.json",
            ['product' => $payload]
        );

        return $response['product'] ?? [];
    }
}
