<?php

namespace App\Domain\Delivery\Shipment\Services;

final class ShipmentStatusMapper
{
    private const CARRIER_STATUS_MAP = [
        'pending' => 'label_created',
        'label_created' => 'label_created',
        'ready_for_pickup' => 'ready_for_pickup',
        'collected' => 'picked_up',
        'picked_up' => 'picked_up',
        'in_transit' => 'out_for_delivery',
        'out_for_delivery' => 'out_for_delivery',
        'delivered' => 'delivered',
        'completed' => 'delivered',
        'delayed' => 'delayed',
        'returned' => 'returned',
        'failed' => 'failure',
        'failure' => 'failure',
        'cancelled' => 'failure',
    ];

    public function mapFromCarrier(string $carrierStatus): string
    {
        $normalized = strtolower(trim($carrierStatus));

        return self::CARRIER_STATUS_MAP[$normalized] ?? $normalized;
    }
}
