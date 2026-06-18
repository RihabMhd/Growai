<?php

namespace App\Application\Delivery\Shipment\Commands;

final readonly class HandleCarrierWebhookCommand
{
    public function __construct(
        public int $deliveryCompanyId,
        public array $payload,
        public ?string $signature = null,
    ) {}
}
