<?php

namespace App\Infrastructure\Delivery\Carriers\Ameex;

use App\Infrastructure\Delivery\Carriers\Generic\AbstractCarrierConnector;
use Illuminate\Support\Facades\Http;


final class AmeexConnector extends AbstractCarrierConnector
{
    protected function authHeaders(): array
    {
        return [
            'X-Api-Key' => $this->credentials['api_key'] ?? '',
            'X-Api-Secret' => $this->credentials['api_secret'] ?? '',
            'Accept' => 'application/json',
        ];
    }

    public function createParcel(array $payload): array
    {
        $ameexPayload = [
            'receiver_name' => $payload['recipient_name'] ?? '',
            'receiver_phone' => $payload['recipient_phone'] ?? '',
            'receiver_address' => $payload['address'] ?? '',
            'receiver_city' => $payload['city'] ?? '',
            'receiver_region' => $payload['region'] ?? '',
            'cod' => $payload['cod_amount'] ?? 0,
            'weight' => $payload['weight'] ?? 1,
            'reference' => $payload['reference'] ?? null,
            'notes' => $payload['delivery_notes'] ?? null,
        ];

        $response = Http::withHeaders($this->authHeaders())
            ->post($this->apiUrl() . '/api/v1/shipments', $ameexPayload);

        if (! $response->successful()) {
            throw new \RuntimeException('Ameex parcel creation failed: ' . $response->body());
        }

        $data = $response->json();

        return [
            'tracking_number' => $data['tracking_code'] ?? $data['tracking_number'] ?? null,
            'raw' => $data,
        ];
    }

    public function getTracking(string $trackingNumber): array
    {
        $response = Http::withHeaders($this->authHeaders())
            ->get($this->apiUrl() . '/api/v1/shipments/' . $trackingNumber . '/tracking');

        if (! $response->successful()) {
            throw new \RuntimeException('Ameex tracking failed: ' . $response->body());
        }

        return $response->json();
    }

    public function cancelParcel(string $trackingNumber): bool
    {
        $response = Http::withHeaders($this->authHeaders())
            ->post($this->apiUrl() . '/api/v1/shipments/' . $trackingNumber . '/cancel');

        return $response->successful();
    }

    public function registerWebhook(): bool
    {
        if (! $this->webhookUrl) {
            return false;
        }

        $response = Http::withHeaders($this->authHeaders())
            ->post($this->apiUrl() . '/api/v1/webhooks', [
                'url' => $this->webhookUrl,
                'events' => ['status_changed', 'delivered', 'returned'],
            ]);

        return $response->successful();
    }

    public function validateWebhook(array $payload): bool
    {
        $signature = $payload['_signature'] ?? null;
        $secret = $this->credentials['webhook_secret'] ?? $this->credentials['api_secret'] ?? '';

        if (! $signature || ! $secret) {
            return isset($payload['tracking_code']) && isset($payload['status']);
        }

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $expected = hash_hmac('sha256', $body, $secret);

        return hash_equals($expected, $signature);
    }
}
