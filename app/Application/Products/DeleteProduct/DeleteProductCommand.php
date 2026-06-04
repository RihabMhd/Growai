<?php

namespace App\Application\Products\DeleteProduct;

final class DeleteProductCommand
{
    public function __construct(
        public readonly int $productId,
        public readonly int $shopId,
    ) {}
}