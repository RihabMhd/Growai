<?php

namespace App\Jobs;

use App\Models\Shop;
use App\Services\ShopifyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncShopifyProductsJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Number of times the job may be attempted before failing.
     */
    public int $tries = 3;

    /**
     * Wait (seconds) before retrying after a failure.
     */
    public int $backoff = 60;

    /**
     * Maximum seconds the job can run before being killed.
     */
    public int $timeout = 120;

    public function __construct(
        private readonly Shop $shop
    ) {}

    public function handle(ShopifyService $shopifyService): void
    {
        Log::info('Starting Shopify product sync', ['shop_id' => $this->shop->id]);

        try {
            $result = $shopifyService->syncProducts($this->shop);

            // Record last sync timestamp on the shop
            $this->shop->update(['last_synced_at' => now()]);

            Log::info('Shopify product sync completed', [
                'shop_id'  => $this->shop->id,
                'imported' => $result['imported'],
                'updated'  => $result['updated'],
                'total'    => $result['total'],
            ]);
        } catch (\Exception $e) {
            Log::error('Shopify product sync job failed', [
                'shop_id' => $this->shop->id,
                'error'   => $e->getMessage(),
            ]);

            // Re-throw so the queue retries according to $tries / $backoff
            throw $e;
        }
    }

    /**
     * Called after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncShopifyProductsJob permanently failed', [
            'shop_id' => $this->shop->id,
            'error'   => $exception->getMessage(),
        ]);

        // Optional: notify via email / Slack here
    }
}