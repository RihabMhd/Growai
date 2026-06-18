<?php

namespace App\Domain\Delivery\DeliveryCompany\Entities;

final readonly class CarrierConfiguration
{
    public function __construct(
        public ?int $id,
        public int $teamId,
        public int $deliveryCompanyId,
        public array $credentials,
        public array $fieldMapping,
        public bool $autoCreateParcel,
        public bool $webhookEnabled,
    ) {}
}
