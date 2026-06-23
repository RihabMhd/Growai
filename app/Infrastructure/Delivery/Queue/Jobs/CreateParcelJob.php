<?php

namespace App\Infrastructure\Delivery\Queue\Jobs;

use App\Domain\Delivery\Shipment\Repositories\ShipmentRepositoryInterface;
use App\Domain\Delivery\Shipment\Services\ShipmentLifecycleService;
use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;
use App\Domain\Orders\Models\Order;
use App\Infrastructure\Delivery\Carriers\CarrierManager;
use App\Infrastructure\Delivery\Services\OrderShipmentSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class CreateParcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $shipmentId,
        public ?float $weight = null,
        public ?array $dimensions = null,
    ) {}

    public function handle(
        ShipmentRepositoryInterface $shipments,
        CarrierManager $carrierManager,
        ShipmentLifecycleService $lifecycle,
        OrderShipmentSyncService $orderSync,
    ): void {
        $shipment = $shipments->findById($this->shipmentId);

        if (! $shipment || ! $shipment->deliveryCompanyId) {
            return;
        }

        try {
            $carrier = $carrierManager->resolve($shipment->deliveryCompanyId);

            $result = $carrier->createParcel([
                'recipient_name' => $shipment->address->recipientName,
                'recipient_phone' => $shipment->address->recipientPhone,
                'address' => $shipment->address->street,
                'city' => $shipment->address->city,
                'region' => $shipment->address->region,
                'country' => $shipment->address->country,
                'cod_amount' => $shipment->codAmount,
                'weight' => $this->weight,
                'dimensions' => $this->dimensions,
                'reference' => 'ORD-' . $shipment->orderId,
            ]);

            $trackingNumber = $result['tracking_number'] ?? $result['parcel_id'] ?? null;

            if (! $trackingNumber) {
                throw new \RuntimeException('Carrier did not return a tracking number.');
            }

            $updated = $shipments->save(
                $shipment
                    ->withTrackingNumber($trackingNumber)
                    ->withStatus(new ShipmentStatusSlug(ShipmentStatusSlug::PICKED_UP))
            );

            $lifecycle->recordStatusChange(
                shipment: $updated,
                newStatus: $updated->status,
                source: 'carrier_api',
                description: 'Parcel created with carrier.',
                payload: $result,
            );

            Order::where('id', $shipment->orderId)->update(['shipment_id' => $updated->id]);
            $orderSync->syncFromShipment($updated);
        } catch (\Throwable $e) {
            Log::error('CreateParcelJob failed', [
                'shipment_id' => $this->shipmentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
