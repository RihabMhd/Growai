<?php

namespace App\Application\Products\GetProduct;

final class GetProductQuery
{
    public function __construct(
        public readonly int $productId,
        public readonly int $shopId,
    ) {}
}