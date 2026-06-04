<?php

namespace App\Application\Products\CreateProduct;

use App\Domain\Products\DTOs\ProductData;

final class CreateProductCommand
{
    public function __construct(
        public readonly ProductData $data,
    ) {}
}