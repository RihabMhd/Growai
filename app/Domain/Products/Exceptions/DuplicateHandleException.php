<?php

namespace App\Domain\Products\Exceptions;

use RuntimeException;

final class DuplicateHandleException extends RuntimeException
{
    public static function forHandle(string $handle, int $shopId): self
    {
        return new self(
            "Handle [{$handle}] is already in use in shop [{$shopId}]."
        );
    }
}