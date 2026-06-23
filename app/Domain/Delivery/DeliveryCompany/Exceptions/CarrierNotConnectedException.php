<?php

namespace App\Domain\Delivery\DeliveryCompany\Exceptions;

final class CarrierNotConnectedException extends \DomainException
{
    public static function forCompany(int $companyId): self
    {
        return new self("Carrier [{$companyId}] is not connected.");
    }
}
