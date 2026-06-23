<?php

namespace App\Application\Delivery\Shipment\Actions;

use App\Application\Delivery\Shipment\Commands\CancelShipmentCommand;
use App\Domain\Delivery\Shipment\Exceptions\ShipmentCannotBeCancelledException;
use App\Domain\Delivery\Shipment\Exceptions\ShipmentNotFoundException;
use App\Domain\Delivery\Shipment\Repositories\ShipmentRepositoryInterface;
use App\Domain\Delivery\Shipment\Services\ShipmentLifecycleService;
use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;
use App\Infrastructure\Delivery\Queue\Jobs\CancelParcelJob;

final class CancelShipmentAction
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $shipments,
        private readonly ShipmentLifecycleService $lifecycle,
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
            $shipment->withStatus(new ShipmentStatusSlug(ShipmentStatusSlug::FAILURE))
        );

        $this->lifecycle->recordStatusChange(
            shipment: $updated,
            newStatus: $updated->status,
            source: 'manual',
            description: 'Shipment cancelled by user.',
        );

        return $updated;
    }
}
