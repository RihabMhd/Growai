<?php

namespace App\Application\Shopify\ProcessWebhook;

use App\Application\Shopify\Contracts\ShopifyWebhookProcessorInterface;

final readonly class ProcessWebhookHandler
{
    public function __construct(
        private ShopifyWebhookProcessorInterface $processor
    ) {}

    public function handle(
        ProcessWebhookCommand $command
    ): void {
        $this->processor->process(
            $command->topic,
            $command->shopDomain,
            $command->payload
        );
    }
}