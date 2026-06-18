<?php

namespace App\Infrastructure\Delivery\Carriers\Cathedis;

use App\Infrastructure\Delivery\Carriers\Generic\AbstractCarrierConnector;
use Illuminate\Support\Facades\Http;

final class CathedisConnector extends AbstractCarrierConnector
{
    protected function authHeaders(): array
    {
        return [
            'Authorization' => 'Basic ' . base64_encode(
                ($this->credentials['username'] ?? '') . ':' . ($this->credentials['password'] ?? '')
            ),
            'Accept' => 'application/json',
        ];
    }

    public function createParcel(array $payload): array
    {
        $response = Http::withHeaders($this->authHeaders())
            ->post($this->apiUrl() . '/orders', [
                'customer' => [
                    'name' => $payload['recipient_name'] ?? '',
                    'phone' => $payload['recipient_phone'] ?? '',
                ],
                'address' => $payload['address'] ?? '',
                'city' => $payload['city'] ?? '',
                'amount' => $payload['cod_amount'] ?? 0,
                'reference' => $payload['reference'] ?? null,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Cathedis parcel creation failed: ' . $response->body());
        }

        $data = $response->json();

        return [
            'tracking_number' => $data['barcode'] ?? $data['tracking_number'] ?? null,
            'raw' => $data,
        ];
    }

    public function validateWebhook(array $payload): bool
    {
        return isset($payload['barcode']) && isset($payload['status']);
    }
}
