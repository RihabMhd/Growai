<?php

namespace App\Infrastructure\Shopify\OAuth;

use App\Application\Shopify\Contracts\ShopifyOAuthClientInterface;
use App\Domain\Shopify\Exceptions\ShopifyOAuthException;
use Illuminate\Support\Facades\Http;

final class ShopifyOAuthClient implements ShopifyOAuthClientInterface
{
    public function exchangeCodeForToken(
        string $shop,
        string $code
    ): string {

        $response = Http::post(
            "https://{$shop}/admin/oauth/access_token",
            [
                'client_id' => config('services.shopify.client_id'),
                'client_secret' => config('services.shopify.client_secret'),
                'code' => $code,
            ]
        );

        if (! $response->successful()) {
            throw new ShopifyOAuthException(
                'Shopify OAuth failed.'
            );
        }

        return $response->json(
            'access_token'
        );
    }
}