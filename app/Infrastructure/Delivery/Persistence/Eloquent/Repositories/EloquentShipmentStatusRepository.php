<?php

namespace App\Infrastructure\Delivery\Persistence\Eloquent\Repositories;

use App\Domain\Delivery\Shipment\Entities\ShipmentStatus;
use App\Domain\Delivery\Shipment\Repositories\ShipmentStatusRepositoryInterface;
use App\Infrastructure\Delivery\Persistence\Eloquent\Mappers\DeliveryMapper;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\ShipmentStatusModel;

final class EloquentShipmentStatusRepository implements ShipmentStatusRepositoryInterface
{
    public function __construct(
        private readonly DeliveryMapper $mapper,
    ) {}

    public function findBySlug(string $slug): ?ShipmentStatus
    {
        $model = ShipmentStatusModel::where('slug', $slug)->first();

        return $model ? $this->mapper->toShipmentStatusEntity($model) : null;
    }

    public function findById(int $id): ?ShipmentStatus
    {
        $model = ShipmentStatusModel::find($id);

        return $model ? $this->mapper->toShipmentStatusEntity($model) : null;
    }

    public function all(): array
    {
        return ShipmentStatusModel::orderBy('position')
            ->get()
            ->map(fn ($model) => $this->mapper->toShipmentStatusEntity($model))
            ->all();
    }
}
