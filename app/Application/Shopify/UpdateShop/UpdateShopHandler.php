<?php

namespace App\Application\Shopify\UpdateShop;

use App\Application\Shopify\Contracts\ShopRepositoryInterface;

final readonly class UpdateShopHandler
{
    public function __construct(
        private ShopRepositoryInterface $shops
    ) {}

    public function handle(
        UpdateShopCommand $command
    )
    {
        return $this->shops->update(
            $command->shopId,
            [
                'name' => $command->name,
                'boutique_name' => $command->boutiqueName,
            ]
        );
    }
}