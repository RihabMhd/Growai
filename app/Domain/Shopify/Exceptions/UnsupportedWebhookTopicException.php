<?php

namespace App\Domain\Shopify\Exceptions;

use RuntimeException;

final class UnsupportedWebhookTopicException extends RuntimeException
{
    public static function topic(string $topic): self
    {
        return new self(
            "Unsupported Shopify webhook topic: {$topic}"
        );
    }
}