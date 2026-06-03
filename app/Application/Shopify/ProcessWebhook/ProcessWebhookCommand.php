<?php

namespace App\Application\Shopify\ProcessWebhook;

final readonly class ProcessWebhookCommand
{
    public function __construct(
        public string $topic,
        public string $shopDomain,
        public array $payload
    ) {}
}