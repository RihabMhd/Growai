<?php

namespace App\Application\Shopify\ListShops;

final readonly class ListShopsQuery
{
    public function __construct(
        public bool $onlyActive = true
    ) {}
}