<?php

namespace App\Infrastructure\Delivery\Persistence\Eloquent\Repositories;

use App\Domain\Delivery\Shipment\Entities\Shipment;
use App\Domain\Delivery\Shipment\Repositories\ShipmentRepositoryInterface;
use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;
use App\Infrastructure\Delivery\Persistence\Eloquent\Mappers\DeliveryMapper;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\ShipmentModel;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\ShipmentStatusModel;

final class EloquentShipmentRepository implements ShipmentRepositoryInterface
{
    public function __construct(
        private readonly DeliveryMapper $mapper,
    ) {}

    public function findById(int $id): ?Shipment
    {
        $model = ShipmentModel::with('status')->find($id);

        return $model ? $this->mapper->toShipmentEntity($model) : null;
    }

    public function findByTrackingNumber(string $trackingNumber, ?int $deliveryCompanyId = null): ?Shipment
    {
        $query = ShipmentModel::with('status')->where('tracking_number', $trackingNumber);

        if ($deliveryCompanyId) {
            $query->where('delivery_company_id', $deliveryCompanyId);
        }

        $model = $query->first();

        return $model ? $this->mapper->toShipmentEntity($model) : null;
    }

    public function findActiveForOrderAndCarrier(int $orderId, int $deliveryCompanyId): ?Shipment
    {
        $failureStatusId = ShipmentStatusModel::where('slug', ShipmentStatusSlug::DELIVERY_FAILED)->value('id');

        $model = ShipmentModel::with('status')
            ->where('order_id', $orderId)
            ->where('delivery_company_id', $deliveryCompanyId)
            ->when($failureStatusId, fn ($q) => $q->where('shipment_status_id', '!=', $failureStatusId))
            ->first();

        return $model ? $this->mapper->toShipmentEntity($model) : null;
    }

    public function findByFilters(?int $orderId, ?string $statusSlug, ?int $deliveryCompanyId): array
    {
        $query = ShipmentModel::with(['status', 'order', 'deliveryCompany'])->latest();

        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        if ($statusSlug) {
            $query->whereHas('status', fn ($q) => $q->where('slug', $statusSlug));
        }

        if ($deliveryCompanyId) {
            $query->where('delivery_company_id', $deliveryCompanyId);
        }

        return $query->get()
            ->map(fn (ShipmentModel $model) => $this->mapper->toShipmentEntity($model))
            ->all();
    }

    public function save(Shipment $shipment): Shipment
    {
        $statusId = ShipmentStatusModel::where('slug', $shipment->status->value)->value('id')
            ?? ShipmentStatusModel::where('slug', ShipmentStatusSlug::LABEL_CREATED)->value('id');

        $attributes = $this->mapper->toShipmentModelAttributes($shipment, $statusId);

        if ($shipment->id) {
            $model = ShipmentModel::findOrFail($shipment->id);
            $model->update($attributes);
        } else {
            $model = ShipmentModel::create($attributes);
        }

        $model->load('status');

        return $this->mapper->toShipmentEntity($model);
    }

    public function delete(int $id): void
    {
        ShipmentModel::destroy($id);
    }
}
