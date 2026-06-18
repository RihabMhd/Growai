<?php

namespace App\Domain\Delivery\DeliveryCompany\Exceptions;

final class DeliveryCompanyNotFoundException extends \DomainException
{
    public static function withId(int $id): self
    {
        return new self("Delivery company [{$id}] not found.");
    }
}
