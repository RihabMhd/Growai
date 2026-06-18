<?php

namespace App\Application\Delivery\Shipment\Actions;

use App\Application\Delivery\Shipment\Commands\UpdateShipmentCommand;
use App\Domain\Delivery\Shipment\Exceptions\ShipmentNotFoundException;
use App\Domain\Delivery\Shipment\Repositories\ShipmentRepositoryInterface;
use App\Domain\Delivery\Shipment\Services\ShipmentLifecycleService;
use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;
use App\Infrastructure\Delivery\Services\OrderShipmentSyncService;

final class UpdateShipmentAction
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $shipments,
        private readonly ShipmentLifecycleService $lifecycle,
        private readonly OrderShipmentSyncService $orderSync,
    ) {}

    public function execute(UpdateShipmentCommand $command): \App\Domain\Delivery\Shipment\Entities\Shipment
    {
        $shipment = $this->shipments->findById($command->shipmentId);

        if (! $shipment) {
            throw ShipmentNotFoundException::withId($command->shipmentId);
        }

        $updated = $shipment;

        if ($command->statusSlug && $command->statusSlug !== $shipment->status->value) {
            $newStatus = new ShipmentStatusSlug($command->statusSlug);
            $updated = $shipment->withStatus($newStatus);

            $this->lifecycle->recordStatusChange(
                shipment: $updated,
                newStatus: $newStatus,
                source: $command->source,
                description: 'Shipment status updated manually.',
            );

            $this->orderSync->syncFromShipment($updated);
        }

        if ($command->deliveryNotes !== null) {
            $updated = new \App\Domain\Delivery\Shipment\Entities\Shipment(
                id: $updated->id,
                orderId: $updated->orderId,
                deliveryCompanyId: $updated->deliveryCompanyId,
                trackingNumber: $updated->trackingNumber,
                status: $updated->status,
                address: $updated->address,
                codAmount: $updated->codAmount,
                deliveryNotes: $command->deliveryNotes,
                shippedAt: $updated->shippedAt,
                deliveredAt: $updated->deliveredAt,
                createdAt: $updated->createdAt,
                updatedAt: $updated->updatedAt,
            );
        }

        return $this->shipments->save($updated);
    }
}
