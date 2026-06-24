<?php

namespace App\Domain\Delivery\Shipment\Repositories;

use App\Domain\Delivery\Shipment\Entities\Shipment;

interface ShipmentRepositoryInterface
{
    public function findById(int $id): ?Shipment;

    public function findByTrackingNumber(string $trackingNumber, ?int $deliveryCompanyId = null): ?Shipment;

    public function findActiveForOrderAndCarrier(int $orderId, int $deliveryCompanyId): ?Shipment;

    public function findByFilters(?int $orderId, ?string $statusSlug, ?int $deliveryCompanyId): array;

    public function save(Shipment $shipment): Shipment;

    public function delete(int $id): void;
}
