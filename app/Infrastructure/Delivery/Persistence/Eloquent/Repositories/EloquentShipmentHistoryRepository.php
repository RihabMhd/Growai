<?php

namespace App\Infrastructure\Delivery\Persistence\Eloquent\Repositories;

use App\Domain\Delivery\Shipment\Entities\ShipmentHistory;
use App\Domain\Delivery\Shipment\Repositories\ShipmentHistoryRepositoryInterface;
use App\Infrastructure\Delivery\Persistence\Eloquent\Mappers\DeliveryMapper;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\ShipmentHistoryModel;

final class EloquentShipmentHistoryRepository implements ShipmentHistoryRepositoryInterface
{
    public function __construct(
        private readonly DeliveryMapper $mapper,
    ) {}

    public function record(ShipmentHistory $history): ShipmentHistory
    {
        $model = ShipmentHistoryModel::create([
            'shipment_id' => $history->shipmentId,
            'old_status' => $history->oldStatus,
            'new_status' => $history->newStatus,
            'source' => $history->source,
            'description' => $history->description,
            'payload' => $history->payload,
            'provider_status' => $history->providerStatus,
            'created_at' => $history->createdAt ?? now(),
        ]);

        return $this->mapper->toShipmentHistoryEntity($model);
    }

    public function findByShipmentId(int $shipmentId): array
    {
        return ShipmentHistoryModel::where('shipment_id', $shipmentId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($model) => $this->mapper->toShipmentHistoryEntity($model))
            ->all();
    }
}
