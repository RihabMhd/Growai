<?php

namespace App\Infrastructure\Delivery\Carriers\Ozon;

use App\Domain\Delivery\Shipment\Services\ShipmentStatusMapperInterface;

final class OzonStatusMapper implements ShipmentStatusMapperInterface
{
    private const MAP = [
        'pending'           => 'label_created',
        'created'           => 'label_created',
        'label_created'     => 'label_created',
        'confirmed'         => 'confirmed',
        'picked_up'         => 'in_transit',
        'collected'         => 'in_transit',
        'in_transit'        => 'in_transit',
        'traveling'         => 'in_transit',
        'travelling'        => 'in_transit',
        'out_for_delivery'  => 'out_for_delivery',
        'delivery'          => 'out_for_delivery',
        'delivered'         => 'delivered',
        'completed'         => 'delivered',
        'received'          => 'fulfilled',
        'attempted_delivery'=> 'attempted_delivery',
        'no_answer'         => 'attempted_delivery',
        'absent'            => 'attempted_delivery',
        'postponed'         => 'delayed',
        'delayed'           => 'delayed',
        'returned'          => 'returned',
        'failed'            => 'delivery_failed',
        'delivery_failed'   => 'delivery_failed',
        'cancelled'         => 'delivery_failed',
        'no_answer_re排送'   => 'attempted_delivery',
    ];

    public function mapFromProvider(string $providerStatus): string
    {
        $normalized = strtolower(trim($providerStatus));

        return self::MAP[$normalized] ?? 'in_transit';
    }

    public function getProviderStatuses(): array
    {
        return [
            ['code' => 'PENDING',           'label' => 'Pending'],
            ['code' => 'CREATED',           'label' => 'Created'],
            ['code' => 'CONFIRMED',         'label' => 'Confirmed'],
            ['code' => 'PICKED_UP',         'label' => 'Picked Up'],
            ['code' => 'COLLECTED',         'label' => 'Collected'],
            ['code' => 'IN_TRANSIT',        'label' => 'In Transit'],
            ['code' => 'TRAVELING',         'label' => 'Traveling'],
            ['code' => 'OUT_FOR_DELIVERY',  'label' => 'Out for Delivery'],
            ['code' => 'DELIVERED',         'label' => 'Delivered'],
            ['code' => 'COMPLETED',         'label' => 'Completed'],
            ['code' => 'RECEIVED',          'label' => 'Received'],
            ['code' => 'ATTEMPTED_DELIVERY','label' => 'Attempted Delivery'],
            ['code' => 'NO_ANSWER',         'label' => 'No Answer'],
            ['code' => 'POSTPONED',         'label' => 'Postponed'],
            ['code' => 'DELAYED',           'label' => 'Delayed'],
            ['code' => 'RETURNED',          'label' => 'Returned'],
            ['code' => 'FAILED',            'label' => 'Failed'],
            ['code' => 'DELIVERY_FAILED',   'label' => 'Delivery Failed'],
            ['code' => 'CANCELLED',         'label' => 'Cancelled'],
        ];
    }
}
