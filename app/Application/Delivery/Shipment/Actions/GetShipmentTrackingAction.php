<?php

namespace App\Application\Delivery\Shipment\Actions;

use App\Application\Delivery\Shipment\Queries\GetShipmentTrackingQuery;
use App\Domain\Delivery\Shipment\Exceptions\ShipmentNotFoundException;
use App\Infrastructure\Delivery\Queue\Jobs\SyncTrackingJob;

final class GetShipmentTrackingAction
{
    public function __construct(
        private readonly \App\Domain\Delivery\Shipment\Repositories\ShipmentRepositoryInterface $shipments,
    ) {}

    public function execute(GetShipmentTrackingQuery $query): array
    {
        $shipment = $this->shipments->findById($query->shipmentId);

        if (! $shipment) {
            throw ShipmentNotFoundException::withId($query->shipmentId);
        }

        if (! $shipment->trackingNumber) {
            throw new \DomainException('No tracking number available.');
        }

        SyncTrackingJob::dispatch($query->shipmentId);

        return [
            'shipment_id' => $shipment->id,
            'tracking_number' => $shipment->trackingNumber,
            'status' => $shipment->status->value,
            'message' => 'Tracking sync queued. Refresh shortly for updated status.',
        ];
    }
}
