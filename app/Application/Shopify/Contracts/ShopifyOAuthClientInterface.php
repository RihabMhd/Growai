<?php

namespace App\Application\Shopify\Contracts;

interface ShopifyOAuthClientInterface
{
    public function exchangeCodeForToken(
        string $shop,
        string $code
    ): string;
}