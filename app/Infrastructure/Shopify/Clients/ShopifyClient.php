<?php

namespace App\Infrastructure\Shopify\Clients;

use App\Application\Shopify\Contracts\ShopifyClientInterface;
use App\Domain\Shopify\Exceptions\ShopifyApiException;
use App\Domain\Shopify\Models\Shop;
use Illuminate\Support\Facades\Http;

final class ShopifyClient implements ShopifyClientInterface
{
    private const API_VERSION = '2025-01';

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

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $shop->access_token,
            'Accept' => 'application/json',
        ])->send(
            $method,
            $url,
            [
                'json' => $payload,
            ]
        );

        if (! $response->successful()) {
            throw new ShopifyApiException(
                sprintf(
                    'Shopify API request failed [%s]: %s',
                    $response->status(),
                    $response->body()
                )
            );
        }

        return $response->json();
    }
}