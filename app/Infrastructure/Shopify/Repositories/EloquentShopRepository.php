<?php

namespace App\Infrastructure\Shopify\Repositories;

use App\Application\Shopify\Contracts\ShopRepositoryInterface;
use App\Domain\Shopify\Models\Shop;

final class EloquentShopRepository implements ShopRepositoryInterface
{
    public function find(int $id): ?Shop
    {
        return Shop::find($id);
    }

    public function findByDomain(string $domain): ?Shop
    {
        return Shop::where(
            'shopify_domain',
            $domain
        )->first();
    }

    public function resolveForRequest(
        ?int $shopId,
        mixed $user
    ): ?Shop {
        if ($shopId) {
            return Shop::where('id', $shopId)
                ->where('is_active', true)
                ->first();
        }

        return Shop::where('is_active', true)
            ->latest()
            ->first();
    }

    public function activeShops()
    {
        return Shop::where(
            'is_active',
            true
        )->latest()->get();
    }

    public function upsert(
        string $domain,
        string $token
    ): Shop {
        return Shop::updateOrCreate(
            [
                'shopify_domain' => $domain,
            ],
            [
                'name' => $domain,
                'access_token' => $token,
                'platform' => 'shopify',
                'is_active' => true,
            ]
        );
    }

    public function update(
        int $shopId,
        array $data
    ): Shop {
        $shop = $this->find($shopId);

        $shop->update($data);

        return $shop->fresh();
    }

    public function disconnect(
        int $shopId
    ): void {
        $shop = $this->find($shopId);

        $shop->update([
            'is_active' => false,
            'access_token' => null,
        ]);
    }
}