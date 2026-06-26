<?php

namespace App\Domain\Delivery\Shipment\Services;

interface ShippingDriverInterface
{
    public function createParcel(array $payload): array;
}

