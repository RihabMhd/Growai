<?php

namespace App\Domain\Delivery\DeliveryCompany\Entities;

final readonly class DeliveryCompany
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $phone,
        public ?string $apiUrl,
        public bool $isActive,
        public bool $hasCredentials,
        public bool $webhookEnabled,
        public ?\DateTimeImmutable $webhookRegisteredAt = null,
    ) {}

    public function isConnected(): bool
    {
        return $this->isActive && $this->hasCredentials;
    }
}
