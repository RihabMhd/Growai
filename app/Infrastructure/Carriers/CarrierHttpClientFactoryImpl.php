<?php

namespace App\Infrastructure\Carriers;

use App\Infrastructure\Carriers\Contracts\CarrierHttpClient;
use App\Infrastructure\Carriers\Contracts\CarrierHttpClientFactory;
use App\Infrastructure\Carriers\Ameex\AmeexHttpClient;
use App\Domain\CarrierActions\Exceptions\CarrierIntegrationNotAvailableException;

final class CarrierHttpClientFactoryImpl implements CarrierHttpClientFactory
{
    public function forCarrier(string $carrierSlug, $config): CarrierHttpClient
    {
        return match ($carrierSlug) {
            'ameex' => new AmeexHttpClient($this->credentialsFor($config)),
            default => throw new CarrierIntegrationNotAvailableException($carrierSlug),
        };
    }

    private function credentialsFor($config): array
    {
        return $config->credentials_json['createParcel'] ?? [];
    }
}