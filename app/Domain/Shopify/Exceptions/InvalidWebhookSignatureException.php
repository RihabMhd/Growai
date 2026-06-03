<?php

namespace App\Domain\Shopify\Exceptions;

use RuntimeException;

final class InvalidWebhookSignatureException extends RuntimeException
{
    protected $message = 'Invalid Shopify webhook signature.';
}