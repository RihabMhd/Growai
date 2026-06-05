<?php

namespace App\Domain\Products\Exceptions;

use RuntimeException;

final class ProductShopMismatchException extends RuntimeException
{
    public static function forProduct(int $productId, int $shopId): self
    {
        return new self(
            "Product [{$productId}] does not belong to shop [{$shopId}]."
        );
    }
}