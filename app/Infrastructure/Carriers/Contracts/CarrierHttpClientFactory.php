<?php

namespace App\Infrastructure\Carriers\Contracts;

interface CarrierHttpClientFactory
{
    public function forCarrier(string $carrierSlug, $config): CarrierHttpClient;
}