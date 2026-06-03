<?php

namespace App\Application\Shopify\SyncShopProducts;

use App\Jobs\SyncShopifyProductsJob;
use App\Application\Shopify\Contracts\ShopRepositoryInterface;

final readonly class SyncShopProductsHandler
{
    public function __construct(
        private ShopRepositoryInterface $shops
    ) {}

    public function handle(
        SyncShopProductsCommand $command
    ): void {
        $shop = $this->shops->find(
            $command->shopId
        );

        SyncShopifyProductsJob::dispatch(
            $shop
        );
    }
}