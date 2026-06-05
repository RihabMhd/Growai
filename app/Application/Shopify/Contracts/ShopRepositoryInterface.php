<?php

namespace App\Application\Shopify\Contracts;

use App\Domain\Shopify\Models\Shop;

interface ShopRepositoryInterface
{
    public function find(int $id): ?Shop;

    public function findByDomain(
        string $domain
    ): ?Shop;

    public function resolveForRequest(
        ?int $shopId,
        mixed $user
    ): ?Shop;

    public function upsert(
        string $domain,
        string $token
    ): Shop;

    public function activeShops();

    public function update(
        int $shopId,
        array $data
    ): Shop;

    public function disconnect(
        int $shopId
    ): void;
}