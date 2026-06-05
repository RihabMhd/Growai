<?php

namespace App\Application\Shopify\DisconnectShop;

use App\Application\Shopify\Contracts\ShopRepositoryInterface;

final readonly class DisconnectShopHandler
{
    public function __construct(
        private ShopRepositoryInterface $shops
    ) {}

    public function handle(
        DisconnectShopCommand $command
    ): void {
        $this->shops->disconnect(
            $command->shopId
        );
    }
}