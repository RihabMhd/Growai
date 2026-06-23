<?php

namespace App\Domain\Delivery\Shipment\ValueObjects;

final readonly class TrackingNumber
{
    public function __construct(public string $value)
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('Tracking number cannot be empty.');
        }
    }
}
