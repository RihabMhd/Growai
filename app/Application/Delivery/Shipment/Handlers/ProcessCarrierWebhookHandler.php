<?php

namespace App\Application\Delivery\Shipment\Handlers;

use App\Domain\Delivery\DeliveryCompany\Repositories\CarrierWebhookLogRepositoryInterface;
use App\Domain\Delivery\Shipment\Repositories\ShipmentRepositoryInterface;
use App\Domain\Delivery\Shipment\Services\ShipmentLifecycleService;
use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;
use App\Infrastructure\Delivery\Carriers\CarrierManager;
use App\Infrastructure\Delivery\Carriers\ShipmentStatusMapperFactory;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\CarrierWebhookLogModel;
use App\Infrastructure\Delivery\Services\OrderShipmentSyncService;
use App\Infrastructure\Delivery\Services\ShipmentNotificationService;
use Illuminate\Support\Facades\Log;

final class ProcessCarrierWebhookHandler
{
    public function __construct(
        private readonly CarrierWebhookLogRepositoryInterface $webhookLogs,
        private readonly ShipmentRepositoryInterface $shipments,
        private readonly ShipmentStatusMapperFactory $mapperFactory,
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

            $mapper = $this->mapperFactory->make($this->resolveCarrierSlug($deliveryCompanyId));
            $fulfillmentSlug = $mapper->mapFromProvider($carrierStatus);
            $mappedStatus = new ShipmentStatusSlug($fulfillmentSlug);

            if ($mappedStatus->value === $shipment->status->value && $carrierStatus === $shipment->providerStatus) {
                $this->webhookLogs->markProcessed($webhookLogId);

                return;
            }

            $updated = $shipment->withStatus($mappedStatus, $carrierStatus);

            if (in_array($mappedStatus->value, [ShipmentStatusSlug::IN_TRANSIT], true) && ! $shipment->shippedAt) {
                $updated = new \App\Domain\Delivery\Shipment\Entities\Shipment(
                    id: $updated->id,
                    orderId: $updated->orderId,
                    deliveryCompanyId: $updated->deliveryCompanyId,
                    trackingNumber: $updated->trackingNumber,
                    status: $updated->status,
                    providerStatus: $updated->providerStatus,
                    address: $updated->address,
                    codAmount: $updated->codAmount,
                    deliveryNotes: $payload['notes'] ?? $updated->deliveryNotes,
                    parcelCode: $updated->parcelCode,
                    externalReference: $updated->externalReference,
                    carrierTrackingNumber: $updated->carrierTrackingNumber,
                    carrierPayload: $updated->carrierPayload,
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
                    providerStatus: $updated->providerStatus,
                    address: $updated->address,
                    codAmount: $updated->codAmount,
                    deliveryNotes: $payload['notes'] ?? $updated->deliveryNotes,
                    parcelCode: $updated->parcelCode,
                    externalReference: $updated->externalReference,
                    carrierTrackingNumber: $updated->carrierTrackingNumber,
                    carrierPayload: $updated->carrierPayload,
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
                providerStatus: $carrierStatus,
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

    private function resolveCarrierSlug(int $deliveryCompanyId): string
    {
        $company = app(\App\Domain\Delivery\DeliveryCompany\Repositories\DeliveryCompanyRepositoryInterface::class)
            ->findById($deliveryCompanyId);

        return $company?->slug ?? 'generic';
    }
}
