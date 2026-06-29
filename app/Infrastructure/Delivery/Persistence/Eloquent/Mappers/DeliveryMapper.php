<?php

namespace App\Infrastructure\Delivery\Persistence\Eloquent\Mappers;

use App\Domain\Delivery\DeliveryCompany\Entities\CarrierConfiguration;
use App\Domain\Delivery\DeliveryCompany\Entities\CarrierWebhookLog;
use App\Domain\Delivery\DeliveryCompany\Entities\DeliveryCompany;
use App\Domain\Delivery\Shipment\Entities\Shipment;
use App\Domain\Delivery\Shipment\Entities\ShipmentHistory;
use App\Domain\Delivery\Shipment\Entities\ShipmentStatus;
use App\Domain\Delivery\Shipment\ValueObjects\Address;
use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\CarrierConfigurationModel;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\CarrierWebhookLogModel;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\DeliveryCompanyModel;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\ShipmentHistoryModel;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\ShipmentModel;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\ShipmentStatusModel;

final class DeliveryMapper
{
    public function toShipmentEntity(ShipmentModel $model): Shipment
    {
        $statusSlug = $model->relationLoaded('status') && $model->status
            ? $model->status->slug
            : ShipmentStatusSlug::LABEL_CREATED;

        return new Shipment(
            id: $model->id,
            orderId: $model->order_id,
            deliveryCompanyId: $model->delivery_company_id,
            trackingNumber: $model->tracking_number,

            parcelCode: $model->parcel_code,
            externalReference: $model->external_reference,
            carrierTrackingNumber: $model->carrier_tracking_number,
            carrierPayload: $model->carrier_payload,

            status: new ShipmentStatusSlug($statusSlug),
            providerStatus: $model->provider_status,
            address: new Address(
                recipientName: $model->recipient_name,
                recipientPhone: $model->recipient_phone,
                street: $model->address,
                city: $model->city,
                region: $model->region,
                country: $model->country ?? 'MA',
            ),
            codAmount: (float) $model->cod_amount,
            deliveryNotes: $model->delivery_notes,
            shippedAt: $model->shipped_at?->toDateTimeImmutable(),
            deliveredAt: $model->delivered_at?->toDateTimeImmutable(),
            createdAt: $model->created_at?->toDateTimeImmutable(),
            updatedAt: $model->updated_at?->toDateTimeImmutable(),
        );
    }

    public function toShipmentModelAttributes(Shipment $entity, int $statusId): array
    {
        return [
            'order_id' => $entity->orderId,
            'delivery_company_id' => $entity->deliveryCompanyId,
            'tracking_number' => $entity->trackingNumber,

            'parcel_code' => $entity->parcelCode,
            'external_reference' => $entity->externalReference,
            'carrier_tracking_number' => $entity->carrierTrackingNumber,
            'carrier_payload' => $entity->carrierPayload,

            'shipment_status_id' => $statusId,
            'provider_status' => $entity->providerStatus,
            'recipient_name' => $entity->address->recipientName,
            'recipient_phone' => $entity->address->recipientPhone,
            'address' => $entity->address->street,
            'city' => $entity->address->city,
            'region' => $entity->address->region,
            'country' => $entity->address->country,
            'cod_amount' => $entity->codAmount,
            'delivery_notes' => $entity->deliveryNotes,
            'shipped_at' => $entity->shippedAt,
            'delivered_at' => $entity->deliveredAt,
        ];
    }

    public function toShipmentStatusEntity(ShipmentStatusModel $model): ShipmentStatus
    {
        return new ShipmentStatus(
            id: $model->id,
            slug: $model->slug,
            name: $model->name,
            color: $model->color,
            position: $model->position,
            isFinal: $model->is_final,
        );
    }

    public function toShipmentHistoryEntity(ShipmentHistoryModel $model): ShipmentHistory
    {
        return new ShipmentHistory(
            id: $model->id,
            shipmentId: $model->shipment_id,
            oldStatus: $model->old_status,
            newStatus: $model->new_status,
            source: $model->source,
            description: $model->description,
            payload: $model->payload,
            providerStatus: $model->provider_status,
            createdAt: $model->created_at?->toDateTimeImmutable(),
        );
    }

    public function toDeliveryCompanyEntity(DeliveryCompanyModel $model): DeliveryCompany
    {
        return new DeliveryCompany(
            id: $model->id,
            name: $model->name,
            slug: $model->slug ?? 'generic',
            phone: $model->phone,
            apiUrl: $model->api_url,
            isActive: (bool) $model->is_active,
            hasCredentials: ! empty($model->api_key),
            webhookEnabled: (bool) $model->webhook_enabled,
            webhookRegisteredAt: $model->webhook_registered_at?->toDateTimeImmutable(),
        );
    }

    public function toCarrierConfigurationEntity(CarrierConfigurationModel $model): CarrierConfiguration
    {
        return new CarrierConfiguration(
            id: $model->id,
            teamId: $model->team_id,
            deliveryCompanyId: $model->delivery_company_id,
            credentials: $model->credentials_json ?? [],
            fieldMapping: $model->field_mapping_json ?? [],
            autoCreateParcel: (bool) $model->auto_create_parcel,
            webhookEnabled: (bool) $model->webhook_enabled,
        );
    }

    public function toCarrierWebhookLogEntity(CarrierWebhookLogModel $model): CarrierWebhookLog
    {
        return new CarrierWebhookLog(
            id: $model->id,
            deliveryCompanyId: $model->delivery_company_id,
            event: $model->event,
            payload: $model->payload ?? [],
            processed: (bool) $model->processed,
            error: $model->error,
            createdAt: $model->created_at?->toDateTimeImmutable(),
        );
    }
}
