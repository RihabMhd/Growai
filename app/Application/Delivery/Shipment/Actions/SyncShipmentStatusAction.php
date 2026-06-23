<?php

namespace App\Application\Delivery\Shipment\Actions;

use App\Application\Delivery\Shipment\Commands\SyncShipmentStatusCommand;
use App\Domain\Delivery\Shipment\Exceptions\ShipmentNotFoundException;
use App\Infrastructure\Delivery\Queue\Jobs\SyncTrackingJob;

final class SyncShipmentStatusAction
{
    public function __construct(
        private readonly \App\Domain\Delivery\Shipment\Repositories\ShipmentRepositoryInterface $shipments,
    ) {}

    public function execute(SyncShipmentStatusCommand $command): void
    {
        $shipment = $this->shipments->findById($command->shipmentId);

        if (! $shipment) {
            throw ShipmentNotFoundException::withId($command->shipmentId);
        }

        if (! $shipment->trackingNumber) {
            throw new \DomainException('Shipment has no tracking number to sync.');
        }

        SyncTrackingJob::dispatch($command->shipmentId);
    }
}
