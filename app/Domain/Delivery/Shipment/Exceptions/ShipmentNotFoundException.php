<?php

namespace App\Domain\Delivery\Shipment\Exceptions;

final class ShipmentNotFoundException extends \DomainException
{
    public static function withId(int $id): self
    {
        return new self("Shipment [{$id}] not found.");
    }

    public static function withTrackingNumber(string $trackingNumber): self
    {
        return new self("Shipment with tracking number [{$trackingNumber}] not found.");
    }
}
