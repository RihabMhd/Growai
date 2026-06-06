<?php

namespace App\Infrastructure\Shopify\Webhooks;

use App\Domain\Shopify\Models\Shop;
use App\Infrastructure\Shopify\Services\ShopifyOrderImporter;

final readonly class OrderWebhookHandler
{
    public function __construct(
        private ShopifyOrderImporter $importer
    ) {}

    public function handleCreated(Shop $shop, array $payload): void
    {
        $this->importer->upsert($shop, $payload);
    }

    public function handleUpdated(Shop $shop, array $payload): void
    {
        $this->importer->upsert($shop, $payload);
    }

    public function handleCancelled(Shop $shop, array $payload): void
    {
        $this->importer->markCancelled($shop, (string) $payload['id']);
    }

    public function handlePaid(Shop $shop, array $payload): void
    {
        $this->importer->markPaid($shop, (string) $payload['id']);
    }

    public function handleFulfilled(Shop $shop, array $payload): void
    {
        $this->importer->markFulfilled($shop, (string) $payload['id']);
    }
}