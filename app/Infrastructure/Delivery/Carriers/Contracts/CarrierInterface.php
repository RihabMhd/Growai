<?php

namespace App\Infrastructure\Delivery\Carriers\Contracts;

interface CarrierInterface
{
    public function createParcel(array $payload): array;

    public function getTracking(string $trackingNumber): array;

    public function cancelParcel(string $trackingNumber): bool;

    public function registerWebhook(): bool;

    public function validateWebhook(array $payload): bool;

    /**
     * Provider-driven parcel statuses for the fulfillment dropdown.
     *
     * @return array<int, array{code: string, label: string, color: string, type: string, icon?: string}>
     */
    public function getAvailableStatuses(): array;
}

