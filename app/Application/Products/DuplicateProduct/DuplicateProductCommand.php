<?php

namespace App\Application\Products\DuplicateProduct;

final class DuplicateProductCommand
{
    public function __construct(
        public readonly int $productId,
        public readonly int $shopId,
    ) {}
}