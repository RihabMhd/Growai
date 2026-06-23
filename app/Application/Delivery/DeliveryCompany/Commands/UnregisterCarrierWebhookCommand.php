<?php

namespace App\Application\Delivery\DeliveryCompany\Commands;

final readonly class UnregisterCarrierWebhookCommand
{
    public function __construct(
        public int $deliveryCompanyId,
        public int $teamId,
    ) {}
}
