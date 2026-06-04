<?php

namespace App\Application\Products\UpdateProduct;

use App\Domain\Products\DTOs\ProductData;

final class UpdateProductCommand
{
    public function __construct(
        public readonly int         $productId,
        public readonly int         $shopId,
        public readonly ProductData $data,
    ) {}
}