<?php

namespace App\Application\Products\SyncProductFromWebhook;

final class SyncProductFromWebhookCommand
{
    public function __construct(
        public readonly int   $shopId,
        public readonly array $shopifyPayload,
        public readonly string $event, // 'products/create' | 'products/update' | 'products/delete'
    ) {}
}