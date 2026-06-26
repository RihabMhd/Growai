<?php

namespace App\Domain\Delivery\Shipment\Exceptions;

final class OrderShipmentAlreadyExistsException extends \DomainException
{
    public static function forOrder(int $orderId): self
    {
        return new self("A shipment already exists for this order [{$orderId}].");
    }
}

