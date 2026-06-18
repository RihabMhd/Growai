<?php

namespace App\Domain\Delivery\DeliveryCompany\Entities;

final readonly class CarrierWebhookLog
{
    public function __construct(
        public ?int $id,
        public int $deliveryCompanyId,
        public ?string $event,
        public array $payload,
        public bool $processed = false,
        public ?string $error = null,
        public ?\DateTimeImmutable $createdAt = null,
    ) {}
}
