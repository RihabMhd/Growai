<?php

namespace App\Application\Shopify\Contracts;

interface ShopifyWebhookProcessorInterface
{
    public function process(
        string $topic,
        string $shopDomain,
        array $payload
    ): void;
}