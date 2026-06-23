<?php

namespace App\Domain\Delivery\Shipment\Exceptions;

final class ShipmentCannotBeCancelledException extends \DomainException
{
    public static function forStatus(string $status): self
    {
        return new self("Shipment in status [{$status}] cannot be cancelled.");
    }
}
