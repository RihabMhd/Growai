<?php

namespace App\Domain\Delivery\Shipment\Services;

use App\Domain\Delivery\Shipment\Entities\Shipment;
use App\Domain\Delivery\Shipment\Entities\ShipmentHistory;
use App\Domain\Delivery\Shipment\Repositories\ShipmentHistoryRepositoryInterface;
use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;

final class ShipmentLifecycleService
{
    public function __construct(
        private readonly ShipmentHistoryRepositoryInterface $history,
    ) {}

    public function recordStatusChange(
        Shipment $shipment,
        ShipmentStatusSlug $newStatus,
        string $source,
        ?string $description = null,
        ?array $payload = null,
        ?string $providerStatus = null,
    ): ShipmentHistory {
        return $this->history->record(new ShipmentHistory(
            id: null,
            shipmentId: $shipment->id,
            oldStatus: $shipment->status->value,
            newStatus: $newStatus->value,
            source: $source,
            description: $description,
            payload: $payload,
            providerStatus: $providerStatus ?? $shipment->providerStatus,
            createdAt: new \DateTimeImmutable,
        ));
    }
}
