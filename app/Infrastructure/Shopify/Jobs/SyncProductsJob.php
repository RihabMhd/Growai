<?php

namespace App\Infrastructure\Shopify\Jobs;

use App\Domain\Shopify\Models\Shop;
use App\Infrastructure\Shopify\Services\ShopifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncProductsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Shop $shop
    ) {}

    public function handle(
        ShopifyService $shopifyService
    ): void {

        try {

            $shopifyService->syncProducts(
                $this->shop
            );

            $this->shop->update([
                'last_synced_at' => now(),
            ]);

        } catch (\Throwable $exception) {

            Log::error(
                'Shopify product sync failed',
                [
                    'shop_id' => $this->shop->id,
                    'error' => $exception->getMessage(),
                ]
            );

            throw $exception;
        }
    }
}