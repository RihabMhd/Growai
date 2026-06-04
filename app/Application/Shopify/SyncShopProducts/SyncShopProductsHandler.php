<?php

namespace App\Application\Shopify\SyncShopProducts;

use App\Application\Shopify\Contracts\ShopRepositoryInterface;
use App\Infrastructure\Shopify\Jobs\SyncProductsJob;

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

        SyncProductsJob::dispatch(
            $shop
        );
    }
}