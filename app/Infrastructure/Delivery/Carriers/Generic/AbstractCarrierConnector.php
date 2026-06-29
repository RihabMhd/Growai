<?php

namespace App\Infrastructure\Delivery\Carriers\Generic;

use App\Infrastructure\Delivery\Carriers\Contracts\CarrierInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractCarrierConnector implements CarrierInterface
{
    public function __construct(
        protected array $credentials,
        protected ?string $webhookUrl = null,
    ) {}

    public function getAvailableStatuses(): array
    {
        return [
            ['code' => 'label_created',      'label' => 'Label Created',      'color' => '#9CA3AF'],
            ['code' => 'in_transit',         'label' => 'In Transit',         'color' => '#3B82F6'],
            ['code' => 'out_for_delivery',   'label' => 'Out for Delivery',   'color' => '#F59E0B'],
            ['code' => 'delivered',          'label' => 'Delivered',          'color' => '#10B981'],
            ['code' => 'attempted_delivery', 'label' => 'Attempted Delivery', 'color' => '#F97316'],
            ['code' => 'delivery_failed',    'label' => 'Delivery Failed',    'color' => '#EF4444'],
            ['code' => 'delayed',            'label' => 'Delayed',            'color' => '#F97316'],
            ['code' => 'returned',           'label' => 'Returned',           'color' => '#8B5CF6'],
        ];
    }


    protected function apiUrl(): string
    {
        return rtrim($this->credentials['api_url'] ?? '', '/');
    }

    protected function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . ($this->credentials['api_key'] ?? ''),
            'Accept' => 'application/json',
        ];
    }

    public function createParcel(array $payload): array
    {
        $response = Http::withHeaders($this->authHeaders())
            ->post($this->apiUrl() . '/parcels', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to create parcel: ' . $response->body());
        }

        return $response->json();
    }

    public function getTracking(string $trackingNumber): array
    {
        $response = Http::withHeaders($this->authHeaders())
            ->get($this->apiUrl() . '/tracking/' . $trackingNumber);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to get tracking: ' . $response->body());
        }

        return $response->json();
    }

    public function cancelParcel(string $trackingNumber): bool
    {
        $response = Http::withHeaders($this->authHeaders())
            ->delete($this->apiUrl() . '/parcels/' . $trackingNumber);

        return $response->successful();
    }

    public function registerWebhook(): bool
    {
        if (! $this->webhookUrl) {
            return false;
        }

        $response = Http::withHeaders($this->authHeaders())
            ->post($this->apiUrl() . '/webhooks', [
                'event' => 'tracking_update',
                'url' => $this->webhookUrl,
                'events' => ['picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'failed'],
            ]);

        return $response->successful();
    }

    public function validateWebhook(array $payload): bool
    {
        return isset($payload['tracking_number']) || isset($payload['parcel_id']);
    }
}
