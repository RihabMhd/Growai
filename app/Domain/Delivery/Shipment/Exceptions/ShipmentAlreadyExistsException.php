<?php

namespace App\Domain\Delivery\Shipment\Exceptions;

final class ShipmentAlreadyExistsException extends \DomainException
{
    public static function forOrderAndCarrier(int $orderId, int $carrierId): self
    {
        return new self("An active shipment already exists for order [{$orderId}] with carrier [{$carrierId}].");
    }
}
