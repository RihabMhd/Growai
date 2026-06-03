<?php

namespace App\Infrastructure\Shopify\Webhooks;

use App\Domain\Shopify\Models\Shop;
use App\Infrastructure\Shopify\Services\ShopifyProductImporter;
use Illuminate\Support\Facades\Log;

final readonly class ProductWebhookHandler
{
    public function __construct(
        private ShopifyProductImporter $importer
    ) {}

    public function handleUpdated(
        Shop $shop,
        array $payload
    ): void {

        $this->importer->updateFromWebhook(
            $shop,
            $payload
        );

        Log::info(
            'Shopify product updated',
            [
                'shop_id' => $shop->id,
                'product_id' => $payload['id'] ?? null,
            ]
        );
    }

    public function handleDeleted(
        Shop $shop,
        array $payload
    ): void {

        if (!isset($payload['id'])) {
            return;
        }

        $this->importer->markDeleted(
            $shop,
            (string) $payload['id']
        );

        Log::info(
            'Shopify product deleted',
            [
                'shop_id' => $shop->id,
                'product_id' => $payload['id'],
            ]
        );
    }
}