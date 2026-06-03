<?php

namespace App\Domain\Shopify\Exceptions;

use RuntimeException;

final class ShopNotFoundException extends RuntimeException
{
    public static function byId(int $shopId): self
    {
        return new self(
            "Shop {$shopId} not found."
        );
    }

    public static function byDomain(string $domain): self
    {
        return new self(
            "Shop {$domain} not found."
        );
    }
}