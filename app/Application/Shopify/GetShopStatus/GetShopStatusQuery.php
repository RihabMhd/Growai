<?php

namespace App\Application\Shopify\GetShopStatus;

final readonly class GetShopStatusQuery
{
    public function __construct(
        public ?int $shopId,
        public mixed $user
    ) {}
}