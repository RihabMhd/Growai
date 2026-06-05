<?php

namespace App\Application\Shopify\ConnectShop;

use App\Application\Shopify\Contracts\ShopRepositoryInterface;
use App\Application\Shopify\Contracts\ShopifyOAuthClientInterface;

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

        return $this->shops->upsert(
            $command->shop,
            $token
        );
    }
}