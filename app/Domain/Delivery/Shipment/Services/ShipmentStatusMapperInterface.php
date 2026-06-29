<?php

namespace App\Domain\Delivery\Shipment\Services;

interface ShipmentStatusMapperInterface
{
    /**
     * Convert a provider-specific status string to a canonical fulfillment status slug.
     */
    public function mapFromProvider(string $providerStatus): string;

    /**
     * Return all provider-specific status codes this mapper handles.
     *
     * @return array<int, array{code: string, label: string}>
     */
    public function getProviderStatuses(): array;
}
