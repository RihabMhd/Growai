<?php

namespace App\Application\Products\ListProducts;

use App\Domain\Products\DTOs\ProductFilterData;

final class ListProductsQuery
{
    public function __construct(
        public readonly int               $shopId,
        public readonly ProductFilterData $filters,
    ) {}
}