<?php

namespace App\Domain\Delivery\Shipment\Services;

final class GenericShippingDriver implements ShippingDriverInterface
{
    public function createParcel(array $payload): array
    {
        $deliveryCompanyId = (int) ($payload['delivery_company_id'] ?? 0);
        $orderId = (int) ($payload['order_id'] ?? 0);

        $externalRef = 'SHP-' . $deliveryCompanyId . '-' . time() . '-' . $orderId;

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

