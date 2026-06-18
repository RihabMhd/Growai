<?php

namespace App\Infrastructure\Delivery\Persistence\Eloquent\Repositories;

use App\Domain\Delivery\DeliveryCompany\Entities\DeliveryCompany;
use App\Domain\Delivery\DeliveryCompany\Repositories\DeliveryCompanyRepositoryInterface;
use App\Infrastructure\Delivery\Persistence\Eloquent\Mappers\DeliveryMapper;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\DeliveryCompanyModel;

final class EloquentDeliveryCompanyRepository implements DeliveryCompanyRepositoryInterface
{
    public function __construct(
        private readonly DeliveryMapper $mapper,
    ) {}

    public function findById(int $id): ?DeliveryCompany
    {
        $model = DeliveryCompanyModel::find($id);

        return $model ? $this->mapper->toDeliveryCompanyEntity($model) : null;
    }

    public function findAll(?bool $activeOnly = null): array
    {
        $query = DeliveryCompanyModel::query();

        if ($activeOnly !== null) {
            $query->where('is_active', $activeOnly);
        }

        return $query->get()
            ->map(fn ($model) => $this->mapper->toDeliveryCompanyEntity($model))
            ->all();
    }

    public function updateConnection(int $id, ?string $apiKey, ?array $credentials, bool $isActive): void
    {
        DeliveryCompanyModel::where('id', $id)->update([
            'api_key' => $apiKey,
            'credentials' => $credentials ? json_encode($credentials) : null,
            'is_active' => $isActive,
        ]);
    }

    public function updateWebhookState(int $id, bool $enabled, ?\DateTimeImmutable $registeredAt = null): void
    {
        DeliveryCompanyModel::where('id', $id)->update([
            'webhook_enabled' => $enabled,
            'webhook_registered_at' => $registeredAt,
        ]);
    }
}
