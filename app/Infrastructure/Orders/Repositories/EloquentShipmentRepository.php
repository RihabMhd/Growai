<?php

namespace App\Infrastructure\Orders\Repositories;

use App\Domain\Orders\Models\Order;
use App\Domain\Delivery\Shipment\ValueObjects\Address;
use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;
use App\Domain\Delivery\Shipment\Entities\Shipment as ShipmentEntity;
use App\Domain\Delivery\Shipment\Repositories\ShipmentRepositoryInterface as DomainShipmentRepositoryInterface;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\ShipmentModel;

class EloquentShipmentRepository implements ShipmentRepositoryInterface
{
    public function __construct(
        private readonly DomainShipmentRepositoryInterface $shipments,
    ) {}

    public function createForOrder(Order $order, array $data): ShipmentModel
    {
        $entity = $this->shipments->save(new ShipmentEntity(
            id: null,
            orderId: $order->id,
            deliveryCompanyId: $data['delivery_company_id'] ?? null,
            trackingNumber: $data['tracking_number'] ?? null,
            status: new ShipmentStatusSlug($data['status'] ?? ShipmentStatusSlug::LABEL_CREATED),
            address: new Address(
                recipientName: $data['recipient_name'] ?? 'N/A',
                recipientPhone: $data['recipient_phone'] ?? 'N/A',
                street: $data['address'] ?? '',
                city: $data['city'] ?? null,
                region: $data['region'] ?? null,
                country: $data['country'] ?? 'MA',
            ),
            codAmount: (float) ($data['cod_amount'] ?? 0),
            deliveryNotes: $data['delivery_notes'] ?? null,
        ));

        return ShipmentModel::with('status')->findOrFail($entity->id);
    }

    public function updateFirstForOrder(Order $order, array $data): ?ShipmentModel
    {
        $shipment = $order->shipments()->first();

        if (! $shipment || empty($data)) {
            return $shipment;
        }

        $entity = $this->shipments->findById($shipment->id);

        if (! $entity) {
            return $shipment;
        }

        $updated = new ShipmentEntity(
            id: $entity->id,
            orderId: $entity->orderId,
            deliveryCompanyId: $data['delivery_company_id'] ?? $entity->deliveryCompanyId,
            trackingNumber: $data['tracking_number'] ?? $entity->trackingNumber,
            status: isset($data['status'])
                ? new ShipmentStatusSlug($data['status'])
                : $entity->status,
            address: new Address(
                recipientName: $data['recipient_name'] ?? $entity->address->recipientName,
                recipientPhone: $data['recipient_phone'] ?? $entity->address->recipientPhone,
                street: $data['address'] ?? $entity->address->street,
                city: $data['city'] ?? $entity->address->city,
                region: $data['region'] ?? $entity->address->region,
                country: $data['country'] ?? $entity->address->country,
            ),
            codAmount: (float) ($data['cod_amount'] ?? $entity->codAmount),
            deliveryNotes: $data['delivery_notes'] ?? $entity->deliveryNotes,
            shippedAt: $entity->shippedAt,
            deliveredAt: $entity->deliveredAt,
            createdAt: $entity->createdAt,
            updatedAt: $entity->updatedAt,
        );

        $this->shipments->save($updated);

        return ShipmentModel::with('status')->find($entity->id);
    }
}
