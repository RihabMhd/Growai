<?php

namespace App\Infrastructure\Shopify\Jobs;

use App\Domain\Shopify\Models\Shop;
use App\Infrastructure\Shopify\Clients\ShopifyClient;
use App\Infrastructure\Shopify\Services\ShopifyOrderImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncOrdersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Shop $shop
    ) {}

    public function handle(
        ShopifyClient $client,
        ShopifyOrderImporter $importer
    ): void {

        try {

            $orders = $client->fetchOrders(
                $this->shop
            );

            $importer->sync(
                $this->shop,
                $orders
            );

        } catch (\Throwable $exception) {

            Log::error(
                'Shopify order sync failed',
                [
                    'shop_id' => $this->shop->id,
                    'error' => $exception->getMessage(),
                ]
            );

            throw $exception;
        }
    }
}
