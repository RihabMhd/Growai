<?php

namespace App\Application\Delivery\Shipment\Actions;

use App\Application\Delivery\Shipment\Commands\CancelShipmentCommand;
use App\Domain\Delivery\Shipment\Exceptions\ShipmentCannotBeCancelledException;
use App\Domain\Delivery\Shipment\Exceptions\ShipmentNotFoundException;
use App\Domain\Delivery\Shipment\Repositories\ShipmentRepositoryInterface;
use App\Domain\Delivery\Shipment\Services\ShipmentLifecycleService;
use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Services\OrderAuditLogger;
use App\Infrastructure\Delivery\Queue\Jobs\CancelParcelJob;

final class CancelShipmentAction
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $shipments,
        private readonly ShipmentLifecycleService $lifecycle,
        private readonly OrderAuditLogger $orderAuditLogger,
    ) {}

    public function execute(CancelShipmentCommand $command): \App\Domain\Delivery\Shipment\Entities\Shipment
    {
        $shipment = $this->shipments->findById($command->shipmentId);

        if (! $shipment) {
            throw ShipmentNotFoundException::withId($command->shipmentId);
        }

        if (! $shipment->canBeCancelled()) {
            throw ShipmentCannotBeCancelledException::forStatus($shipment->status->value);
        }

        if ($shipment->trackingNumber && $shipment->deliveryCompanyId) {
            CancelParcelJob::dispatch($shipment->id);
        }

        $updated = $this->shipments->save(
            $shipment->withStatus(new ShipmentStatusSlug(ShipmentStatusSlug::DELIVERY_FAILED))
        );

        $this->lifecycle->recordStatusChange(
            shipment: $updated,
            newStatus: $updated->status,
            source: 'manual',
            description: 'Shipment cancelled by user.',
        );

        $order = Order::find($shipment->orderId);
        if ($order) {
            $this->orderAuditLogger->log(
                order: $order,
                userId: auth()->id(),
                actionType: 'parcel_cancelled',
                oldValue: $shipment->trackingNumber ?? 'pending',
                newValue: 'cancelled',
                description: 'Parcel cancelled for shipment #' . $shipment->id,
            );
        }

        return $updated;
    }
}
