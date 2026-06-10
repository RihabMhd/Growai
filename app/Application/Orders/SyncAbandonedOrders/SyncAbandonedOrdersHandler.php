<?php

namespace App\Application\Orders\SyncAbandonedOrders;

/**
 * Stub use case for syncing abandoned orders from Shopify.
 *
 * When implemented, this handler should:
 *   1. Fetch abandoned checkouts from the Shopify API via a ShopifyGateway interface
 *   2. Upsert each as an Order with is_abandoned = true
 *   3. Fire OrderCreated for each new order (triggers auto-dispatch if configured)
 *   4. Return a count of synced orders
 */
class SyncAbandonedOrdersHandler
{
    public function handle(): array
    {
        return [
            'synced'  => 0,
            'message' => 'Shopify sync not yet configured.',
        ];
    }
}