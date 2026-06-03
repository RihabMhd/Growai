<?php

namespace App\Application\Shopify\GetShopStatus;

use App\Application\Shopify\Contracts\ShopRepositoryInterface;
use App\Domain\Orders\Models\Order;
use App\Domain\Products\Models\Product;

final readonly class GetShopStatusHandler
{
    public function __construct(
        private ShopRepositoryInterface $shops
    ) {}

    public function handle(
        GetShopStatusQuery $query
    ): ShopStatusResult {

        $shop = $this->shops->resolveForRequest(
            $query->shopId,
            $query->user
        );

        if (!$shop) {
            return new ShopStatusResult(
                connected: false
            );
        }

        return new ShopStatusResult(
            connected: true,
            shop: $shop,
            productCount: Product::where(
                'shop_id',
                $shop->id
            )->where(
                'status',
                'active'
            )->count(),
            orderCount: Order::where(
                'shop_id',
                $shop->id
            )->count(),
            lastSyncedAt: $shop->last_synced_at?->toISOString()
        );
    }
}