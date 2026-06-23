<?php

namespace App\Application\Delivery\Shipment\Handlers;

use App\Domain\Delivery\DeliveryCompany\Repositories\CarrierWebhookLogRepositoryInterface;
use App\Domain\Delivery\Shipment\Repositories\ShipmentRepositoryInterface;
use App\Domain\Delivery\Shipment\Services\ShipmentLifecycleService;
use App\Domain\Delivery\Shipment\Services\ShipmentStatusMapper;
use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;
use App\Infrastructure\Delivery\Carriers\CarrierManager;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\CarrierWebhookLogModel;
use App\Infrastructure\Delivery\Services\OrderShipmentSyncService;
use App\Infrastructure\Delivery\Services\ShipmentNotificationService;
use Illuminate\Support\Facades\Log;

final class ProcessCarrierWebhookHandler
{
    public function __construct(
        private readonly CarrierWebhookLogRepositoryInterface $webhookLogs,
        private readonly ShipmentRepositoryInterface $shipments,
        private readonly ShipmentStatusMapper $statusMapper,
        private readonly ShipmentLifecycleService $lifecycle,
        private readonly CarrierManager $carrierManager,
        private readonly OrderShipmentSyncService $orderSync,
        private readonly ShipmentNotificationService $notifications,
    ) {}

    public function handle(int $webhookLogId, int $deliveryCompanyId, ?string $signature = null): void
    {
        $logModel = CarrierWebhookLogModel::find($webhookLogId);

        if (! $logModel) {
            return;
        }

        $payload = $logModel->payload ?? [];

        try {
            $carrier = $this->carrierManager->resolve($deliveryCompanyId);

            if ($signature && ! $carrier->validateWebhook(array_merge($payload, ['_signature' => $signature]))) {
                throw new \RuntimeException('Invalid webhook signature.');
            }

            $trackingNumber = $payload['tracking_number']
                ?? $payload['parcel_id']
                ?? $payload['tracking_code']
                ?? $payload['barcode']
                ?? $payload['num_suivi']
                ?? null;

            $carrierStatus = $payload['status'] ?? $payload['statut'] ?? null;

            if (! $trackingNumber || ! $carrierStatus) {
                throw new \RuntimeException('Missing tracking number or status in webhook payload.');
            }

            $shipment = $this->shipments->findByTrackingNumber($trackingNumber, $deliveryCompanyId);

            if (! $shipment) {
                throw new \RuntimeException("Shipment not found for tracking [{$trackingNumber}].");
            }

            $mappedStatus = new ShipmentStatusSlug($this->statusMapper->mapFromCarrier($carrierStatus));

            if ($mappedStatus->value === $shipment->status->value) {
                $this->webhookLogs->markProcessed($webhookLogId);

                return;
            }

            $updated = $shipment->withStatus($mappedStatus);

            if (in_array($mappedStatus->value, [ShipmentStatusSlug::PICKED_UP], true)) {
                $updated = new \App\Domain\Delivery\Shipment\Entities\Shipment(
                    id: $updated->id,
                    orderId: $updated->orderId,
                    deliveryCompanyId: $updated->deliveryCompanyId,
                    trackingNumber: $updated->trackingNumber,
                    status: $updated->status,
                    address: $updated->address,
                    codAmount: $updated->codAmount,
                    deliveryNotes: $payload['notes'] ?? $updated->deliveryNotes,
                    shippedAt: new \DateTimeImmutable,
                    deliveredAt: $updated->deliveredAt,
                    createdAt: $updated->createdAt,
                    updatedAt: $updated->updatedAt,
                );
            }

            if ($mappedStatus->value === ShipmentStatusSlug::DELIVERED) {
                $updated = new \App\Domain\Delivery\Shipment\Entities\Shipment(
                    id: $updated->id,
                    orderId: $updated->orderId,
                    deliveryCompanyId: $updated->deliveryCompanyId,
                    trackingNumber: $updated->trackingNumber,
                    status: $updated->status,
                    address: $updated->address,
                    codAmount: $updated->codAmount,
                    deliveryNotes: $payload['notes'] ?? $updated->deliveryNotes,
                    shippedAt: $updated->shippedAt,
                    deliveredAt: new \DateTimeImmutable,
                    createdAt: $updated->createdAt,
                    updatedAt: $updated->updatedAt,
                );
            }

            $saved = $this->shipments->save($updated);

            $this->lifecycle->recordStatusChange(
                shipment: $saved,
                newStatus: $mappedStatus,
                source: 'webhook',
                description: 'Status updated from carrier webhook.',
                payload: $payload,
            );

            $this->orderSync->syncFromShipment($saved);
            $this->notifications->notifyStatusChange($saved);

            $this->webhookLogs->markProcessed($webhookLogId);
        } catch (\Throwable $e) {
            Log::error('Carrier webhook processing failed', [
                'webhook_log_id' => $webhookLogId,
                'error' => $e->getMessage(),
            ]);

            $this->webhookLogs->markProcessed($webhookLogId, $e->getMessage());
        }
    }
}
