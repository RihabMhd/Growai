<?php

namespace App\Application\Delivery\DeliveryCompany\Commands;

final readonly class RegisterCarrierWebhookCommand
{
    public function __construct(
        public int $deliveryCompanyId,
        public int $teamId,
        public string $host,
    ) {}
}
