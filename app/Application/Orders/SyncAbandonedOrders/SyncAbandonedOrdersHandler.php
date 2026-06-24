<?php

namespace App\Application\Orders\SyncAbandonedOrders;


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