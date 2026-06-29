<?php

namespace App\Infrastructure\Delivery\Carriers\Cathedis;

use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;
use App\Infrastructure\Delivery\Carriers\Generic\AbstractCarrierConnector;
use Illuminate\Support\Facades\Http;

final class CathedisConnector extends AbstractCarrierConnector
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
