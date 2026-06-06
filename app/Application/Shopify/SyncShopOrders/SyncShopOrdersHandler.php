<?php

namespace App\Application\Shopify\SyncShopOrders;

use App\Application\Shopify\Contracts\ShopRepositoryInterface;
use App\Infrastructure\Shopify\Jobs\SyncOrdersJob;

final readonly class SyncShopOrdersHandler
{
    public function __construct(
        private ShopRepositoryInterface $shops
    ) {}

    public function handle(
        SyncShopOrdersCommand $command
    ): void {
        $shop = $this->shops->find(
            $command->shopId
        );

        SyncOrdersJob::dispatch(
            $shop
        );
    }
}