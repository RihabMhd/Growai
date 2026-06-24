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

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 120;

    public function __construct(
        private readonly Shop $shop
    ) {}

    public function handle(ShopifyService $shopifyService): void
    {
        Log::info('Starting Shopify product sync', ['shop_id' => $this->shop->id]);

        try {
            $result = $shopifyService->syncProducts($this->shop);

            // record last sync timestamp on the shop
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

            // re-throw to trigger queue retries
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncShopifyProductsJob permanently failed', [
            'shop_id' => $this->shop->id,
            'error'   => $exception->getMessage(),
        ]);

        // optional notification
    }
}