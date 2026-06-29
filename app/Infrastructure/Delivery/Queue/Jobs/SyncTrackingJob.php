<?php

namespace App\Infrastructure\Delivery\Queue\Jobs;

use App\Domain\Delivery\DeliveryCompany\Repositories\DeliveryCompanyRepositoryInterface;
use App\Domain\Delivery\Shipment\Repositories\ShipmentRepositoryInterface;
use App\Domain\Delivery\Shipment\Services\ShipmentLifecycleService;
use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;
use App\Infrastructure\Delivery\Carriers\CarrierManager;
use App\Infrastructure\Delivery\Carriers\ShipmentStatusMapperFactory;
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
        ShipmentStatusMapperFactory $mapperFactory,
        ShipmentLifecycleService $lifecycle,
        OrderShipmentSyncService $orderSync,
        DeliveryCompanyRepositoryInterface $companies,
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

        $company = $companies->findById($shipment->deliveryCompanyId);
        $mapper = $mapperFactory->make($company?->slug ?? 'generic');
        $fulfillmentSlug = $mapper->mapFromProvider($carrierStatus);

        if ($fulfillmentSlug === $shipment->status->value && $carrierStatus === $shipment->providerStatus) {
            return;
        }

        $updated = $shipments->save(
            $shipment->withStatus(new ShipmentStatusSlug($fulfillmentSlug), $carrierStatus)
        );

        $lifecycle->recordStatusChange(
            shipment: $updated,
            newStatus: $updated->status,
            source: 'carrier_sync',
            description: 'Status synced from carrier tracking API.',
            payload: $tracking,
            providerStatus: $carrierStatus,
        );

        $orderSync->syncFromShipment($updated);
    }
}
