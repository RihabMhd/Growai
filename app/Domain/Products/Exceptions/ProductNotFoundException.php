<?php

namespace App\Domain\Products\Exceptions;

use RuntimeException;

final class ProductNotFoundException extends RuntimeException
{
    public static function withId(int $id): self
    {
        return new self("Product [{$id}] not found.");
    }

    public static function withHandle(string $handle): self
    {
        return new self("Product with handle [{$handle}] not found.");
    }

    public static function withIdAndShop(int $id, int $shopId): self
    {
        return new self("Product [{$id}] not found in shop [{$shopId}].");
    }
}