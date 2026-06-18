<?php

namespace App\Infrastructure\Delivery\Carriers\ChronoDiali;

use App\Infrastructure\Delivery\Carriers\Generic\AbstractCarrierConnector;
use Illuminate\Support\Facades\Http;

final class ChronoDialiConnector extends AbstractCarrierConnector
{
    protected function authHeaders(): array
    {
        return [
            'Authorization' => 'Token ' . ($this->credentials['api_key'] ?? ''),
            'Accept' => 'application/json',
        ];
    }

    public function createParcel(array $payload): array
    {
        $response = Http::withHeaders($this->authHeaders())
            ->post($this->apiUrl() . '/expeditions', [
                'destinataire' => $payload['recipient_name'] ?? '',
                'telephone' => $payload['recipient_phone'] ?? '',
                'adresse' => $payload['address'] ?? '',
                'ville' => $payload['city'] ?? '',
                'montant_cr' => $payload['cod_amount'] ?? 0,
                'reference' => $payload['reference'] ?? null,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Chrono Diali parcel creation failed: ' . $response->body());
        }

        $data = $response->json();

        return [
            'tracking_number' => $data['num_suivi'] ?? $data['tracking_number'] ?? null,
            'raw' => $data,
        ];
    }

    public function validateWebhook(array $payload): bool
    {
        return isset($payload['num_suivi']) && isset($payload['statut']);
    }
}
