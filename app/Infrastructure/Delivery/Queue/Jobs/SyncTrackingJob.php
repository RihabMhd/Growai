<?php

namespace App\Infrastructure\Delivery\Queue\Jobs;

use App\Domain\Delivery\Shipment\Repositories\ShipmentRepositoryInterface;
use App\Domain\Delivery\Shipment\Services\ShipmentLifecycleService;
use App\Domain\Delivery\Shipment\Services\ShipmentStatusMapper;
use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;
use App\Infrastructure\Delivery\Carriers\CarrierManager;
use App\Infrastructure\Delivery\Services\OrderShipmentSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SyncTrackingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $shipmentId) {}

    public function handle(
        ShipmentRepositoryInterface $shipments,
        CarrierManager $carrierManager,
        ShipmentStatusMapper $statusMapper,
        ShipmentLifecycleService $lifecycle,
        OrderShipmentSyncService $orderSync,
    ): void {
        $shipment = $shipments->findById($this->shipmentId);

        if (! $shipment?->trackingNumber || ! $shipment->deliveryCompanyId) {
            return;
        }

        $carrier = $carrierManager->resolve($shipment->deliveryCompanyId);
        $tracking = $carrier->getTracking($shipment->trackingNumber);

        $carrierStatus = $tracking['status'] ?? $tracking['current_status'] ?? null;

        if (! $carrierStatus) {
            return;
        }

        $mappedSlug = $statusMapper->mapFromCarrier($carrierStatus);

        if ($mappedSlug === $shipment->status->value) {
            return;
        }

        $updated = $shipments->save(
            $shipment->withStatus(new ShipmentStatusSlug($mappedSlug))
        );

        $lifecycle->recordStatusChange(
            shipment: $updated,
            newStatus: $updated->status,
            source: 'carrier_sync',
            description: 'Status synced from carrier tracking API.',
            payload: $tracking,
        );

        $orderSync->syncFromShipment($updated);
    }
}
