<?php

namespace App\Infrastructure\Delivery\Carriers\ChronoDiali;

use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;
use App\Infrastructure\Delivery\Carriers\Generic\AbstractCarrierConnector;
use Illuminate\Support\Facades\Http;

final class ChronoDialiConnector extends AbstractCarrierConnector
{
    public function getAvailableStatuses(): array
    {
        return [
            ['code' => ShipmentStatusSlug::UNFULFILLED,        'label' => 'Unfulfilled',        'color' => '#6B7280'],
            ['code' => ShipmentStatusSlug::LABEL_CREATED,      'label' => 'Label Created',      'color' => '#9CA3AF'],
            ['code' => ShipmentStatusSlug::CONFIRMED,          'label' => 'Confirmed',          'color' => '#22C55E'],
            ['code' => ShipmentStatusSlug::IN_TRANSIT,         'label' => 'In Transit',         'color' => '#3B82F6'],
            ['code' => ShipmentStatusSlug::OUT_FOR_DELIVERY,   'label' => 'Out for Delivery',   'color' => '#F59E0B'],
            ['code' => ShipmentStatusSlug::DELIVERED,          'label' => 'Delivered',          'color' => '#10B981'],
            ['code' => ShipmentStatusSlug::ATTEMPTED_DELIVERY, 'label' => 'Attempted Delivery', 'color' => '#F97316'],
            ['code' => ShipmentStatusSlug::DELIVERY_FAILED,    'label' => 'Delivery Failed',    'color' => '#EF4444'],
            ['code' => ShipmentStatusSlug::DELAYED,            'label' => 'Delayed',            'color' => '#F97316'],
            ['code' => ShipmentStatusSlug::RETURNED,           'label' => 'Returned',           'color' => '#8B5CF6'],
        ];
    }

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
