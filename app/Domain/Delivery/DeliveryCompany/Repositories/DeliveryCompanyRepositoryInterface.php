<?php

namespace App\Domain\Delivery\DeliveryCompany\Repositories;

use App\Domain\Delivery\DeliveryCompany\Entities\DeliveryCompany;

interface DeliveryCompanyRepositoryInterface
{
    public function findById(int $id): ?DeliveryCompany;

    /** @return DeliveryCompany[] */
    public function findAll(?bool $activeOnly = null): array;

    public function updateConnection(int $id, ?string $apiKey, ?array $credentials, bool $isActive): void;

    public function updateWebhookState(int $id, bool $enabled, ?\DateTimeImmutable $registeredAt = null): void;
}
