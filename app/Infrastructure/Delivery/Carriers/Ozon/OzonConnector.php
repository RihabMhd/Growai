<?php

namespace App\Infrastructure\Delivery\Carriers\Ozon;

use App\Infrastructure\Delivery\Carriers\Generic\AbstractCarrierConnector;
use Illuminate\Support\Facades\Http;

final class OzonConnector extends AbstractCarrierConnector
{
    protected function authHeaders(): array
    {
        return [
            'X-Ozon-Token' => $this->credentials['api_key'] ?? '',
            'Accept' => 'application/json',
        ];
    }

    public function createParcel(array $payload): array
    {
        $response = Http::withHeaders($this->authHeaders())
            ->post($this->apiUrl() . '/v2/parcels', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('Ozon parcel creation failed: ' . $response->body());
        }

        $data = $response->json();

        return [
            'tracking_number' => $data['parcel_id'] ?? $data['tracking_number'] ?? null,
            'raw' => $data,
        ];
    }

    public function validateWebhook(array $payload): bool
    {
        return isset($payload['parcel_id']) && isset($payload['status']);
    }
}
