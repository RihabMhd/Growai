<?php

namespace App\Application\Delivery\DeliveryCompany\Commands;

final readonly class ConnectCarrierCommand
{
    public function __construct(
        public int $deliveryCompanyId,
        public int $teamId,
        public string $apiKey,
        public ?string $apiSecret = null,
        public ?string $username = null,
        public ?string $password = null,
        public ?array $fieldMapping = null,
    ) {}
}
