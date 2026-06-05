<?php

namespace App\Infrastructure\Shopify\Jobs;

use App\Domain\Shopify\DTOs\ShopifyWebhookPayloadDTO;
use App\Infrastructure\Shopify\Webhooks\ShopifyWebhookHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public readonly ShopifyWebhookPayloadDTO $payload
    ) {}

    public function handle(
        ShopifyWebhookHandler $handler
    ): void {
        try {
            $handler->handle($this->payload);
        } catch (\Throwable $exception) {
            Log::error('Shopify webhook processing failed', [
                'topic'       => $this->payload->topic,
                'shop_domain' => $this->payload->shopDomain,
                'error'       => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}