<?php

namespace App\Domain\Delivery\Shipment\Entities;

use App\Domain\Delivery\Shipment\ValueObjects\Address;
use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;

final class Shipment
{
    public function __construct(
        public ?int $id,
        public int $orderId,
        public ?int $deliveryCompanyId,
        public ?string $trackingNumber,

        // Generic carrier identifiers (schema-agnostic)
        public ?string $parcelCode = null,
        public ?string $externalReference = null,
        public ?string $carrierTrackingNumber = null,
        public ?array $carrierPayload = null,

        public ShipmentStatusSlug $status,
        public Address $address,
        public float $codAmount = 0.0,
        public ?string $deliveryNotes = null,
        public ?\DateTimeImmutable $shippedAt = null,
        public ?\DateTimeImmutable $deliveredAt = null,
        public ?\DateTimeImmutable $createdAt = null,
        public ?\DateTimeImmutable $updatedAt = null,
    ) {}

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    public function isInTransit(): bool
    {
        return $this->status->isInTransit();
    }

    public function canBeCancelled(): bool
    {
        return ! $this->isFinal();
    }

    public function withStatus(ShipmentStatusSlug $status): self
    {
        return new self(
            id: $this->id,
            orderId: $this->orderId,
            deliveryCompanyId: $this->deliveryCompanyId,
            trackingNumber: $this->trackingNumber,

            parcelCode: $this->parcelCode,
            externalReference: $this->externalReference,
            carrierTrackingNumber: $this->carrierTrackingNumber,
            carrierPayload: $this->carrierPayload,

            status: $status,
            address: $this->address,
            codAmount: $this->codAmount,
            deliveryNotes: $this->deliveryNotes,
            shippedAt: $this->shippedAt,
            deliveredAt: $this->deliveredAt,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }

    public function withTrackingNumber(string $trackingNumber): self
    {
        return new self(
            id: $this->id,
            orderId: $this->orderId,
            deliveryCompanyId: $this->deliveryCompanyId,
            trackingNumber: $trackingNumber,

            parcelCode: $this->parcelCode,
            externalReference: $this->externalReference,
            carrierTrackingNumber: $this->carrierTrackingNumber,
            carrierPayload: $this->carrierPayload,

            status: $this->status,
            address: $this->address,
            codAmount: $this->codAmount,
            deliveryNotes: $this->deliveryNotes,
            shippedAt: $this->shippedAt,
            deliveredAt: $this->deliveredAt,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }

    public function withCarrierIdentifiers(
        ?string $parcelCode,
        ?string $externalReference,
        ?string $carrierTrackingNumber,
        ?array $carrierPayload,
    ): self {
        return new self(
            id: $this->id,
            orderId: $this->orderId,
            deliveryCompanyId: $this->deliveryCompanyId,
            trackingNumber: $this->trackingNumber,

            parcelCode: $parcelCode,
            externalReference: $externalReference,
            carrierTrackingNumber: $carrierTrackingNumber,
            carrierPayload: $carrierPayload,

            status: $this->status,
            address: $this->address,
            codAmount: $this->codAmount,
            deliveryNotes: $this->deliveryNotes,
            shippedAt: $this->shippedAt,
            deliveredAt: $this->deliveredAt,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }
}
