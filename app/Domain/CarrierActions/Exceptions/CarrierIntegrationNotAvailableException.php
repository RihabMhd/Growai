<?php

namespace App\Domain\CarrierActions\Exceptions;

use RuntimeException;

final class CarrierIntegrationNotAvailableException extends RuntimeException
{
    public function __construct(string $carrierSlug)
    {
        parent::__construct("Integration not available yet for carrier [{$carrierSlug}].");
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'Integration not available yet'], 501);
    }
}