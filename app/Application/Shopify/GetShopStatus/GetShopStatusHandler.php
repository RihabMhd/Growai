<?php

namespace App\Application\Shopify\GetShopStatus;

use App\Application\Shopify\Contracts\ShopRepositoryInterface;

final readonly class GetShopStatusHandler
{
    public function __construct(
        private ShopRepositoryInterface $shops
    ) {}

    public function handle(
        GetShopStatusQuery $query
    ): array {
        $shop = $this->shops
            ->resolveForRequest(
                $query->shopId,
                $query->user
            );

        if (!$shop) {
            return [
                'connected' => false,
            ];
        }

        return [
            'connected' => true,
            'shop' => $shop,
        ];
    }
}