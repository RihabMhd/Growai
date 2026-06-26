<?php

namespace App\Domain\Delivery\Shipment\Services;

final class AmeexShippingDriver implements ShippingDriverInterface
{
    public function createParcel(array $payload): array
    {

        $orderId = (int) ($payload['order_id'] ?? 0);

        $externalRef = 'AMX-' . time() . '-' . $orderId;

        return [
            'external_reference' => $externalRef,
            'tracking_number' => $externalRef,
            'raw' => [
                'stub' => true,
                'payload' => $payload,
            ],
        ];
    }
}

