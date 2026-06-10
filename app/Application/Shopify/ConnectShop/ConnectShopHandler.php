<?php

namespace App\Application\Shopify\ConnectShop;

use App\Application\Shopify\Contracts\ShopRepositoryInterface;
use App\Application\Shopify\Contracts\ShopifyOAuthClientInterface;
use App\Infrastructure\Shopify\Jobs\SyncProductsJob;
use App\Infrastructure\Shopify\Jobs\SyncOrdersJob;

final readonly class ConnectShopHandler
{
    public function __construct(
        private ShopifyOAuthClientInterface $oauthClient,
        private ShopRepositoryInterface $shops
    ) {}

    public function handle(
        ConnectShopCommand $command
    )
    {
        $token = $this->oauthClient
            ->exchangeCodeForToken(
                $command->shop,
                $command->code
            );

        $shop = $this->shops->upsert(
            $command->shop,
            $token
        );

        SyncProductsJob::dispatch($shop);

        // SyncOrdersJob::dispatch($shop);

        return $shop;
    }
}