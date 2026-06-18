<?php

namespace App\Infrastructure\Delivery\Queue\Jobs;

use App\Domain\Delivery\Shipment\Repositories\ShipmentRepositoryInterface;
use App\Infrastructure\Delivery\Carriers\CarrierManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class CancelParcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $shipmentId) {}

    public function handle(
        ShipmentRepositoryInterface $shipments,
        CarrierManager $carrierManager,
    ): void {
        $shipment = $shipments->findById($this->shipmentId);

        if (! $shipment?->trackingNumber || ! $shipment->deliveryCompanyId) {
            return;
        }

        $carrier = $carrierManager->resolve($shipment->deliveryCompanyId);
        $carrier->cancelParcel($shipment->trackingNumber);
    }
}
