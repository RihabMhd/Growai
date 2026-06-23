<?php

namespace App\Infrastructure\Delivery\Carriers\Contracts;

interface CarrierInterface
{
    public function createParcel(array $payload): array;

    public function getTracking(string $trackingNumber): array;

    public function cancelParcel(string $trackingNumber): bool;

    public function registerWebhook(): bool;

    public function validateWebhook(array $payload): bool;
}
