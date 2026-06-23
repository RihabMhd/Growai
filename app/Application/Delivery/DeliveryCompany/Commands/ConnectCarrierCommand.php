<?php

namespace App\Application\Delivery\DeliveryCompany\Commands;

final readonly class ConnectCarrierCommand
{
    public function __construct(
        public int $deliveryCompanyId,
        public int $teamId,
        public array $credentials,
        public ?array $fieldMapping = null,
    ) {}
}