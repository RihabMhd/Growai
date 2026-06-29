<?php

namespace App\Infrastructure\Delivery\Carriers\Generic;

use App\Domain\Delivery\Shipment\Services\ShipmentStatusMapperInterface;
use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;

final class GenericStatusMapper implements ShipmentStatusMapperInterface
{
    private const MAP = [
        'pending'           => ShipmentStatusSlug::LABEL_CREATED,
        'label_created'     => ShipmentStatusSlug::LABEL_CREATED,
        'confirmed'         => ShipmentStatusSlug::CONFIRMED,
        'picked_up'         => ShipmentStatusSlug::IN_TRANSIT,
        'collected'         => ShipmentStatusSlug::IN_TRANSIT,
        'in_transit'        => ShipmentStatusSlug::IN_TRANSIT,
        'out_for_delivery'  => ShipmentStatusSlug::OUT_FOR_DELIVERY,
        'delivered'         => ShipmentStatusSlug::DELIVERED,
        'completed'         => ShipmentStatusSlug::DELIVERED,
        'received'          => ShipmentStatusSlug::FULFILLED,
        'attempted_delivery'=> ShipmentStatusSlug::ATTEMPTED_DELIVERY,
        'no_answer'         => ShipmentStatusSlug::ATTEMPTED_DELIVERY,
        'postponed'         => ShipmentStatusSlug::DELAYED,
        'delayed'           => ShipmentStatusSlug::DELAYED,
        'returned'          => ShipmentStatusSlug::RETURNED,
        'failed'            => ShipmentStatusSlug::DELIVERY_FAILED,
        'delivery_failed'   => ShipmentStatusSlug::DELIVERY_FAILED,
        'cancelled'         => ShipmentStatusSlug::DELIVERY_FAILED,
        'partial'           => ShipmentStatusSlug::PARTIAL,
    ];

    public function mapFromProvider(string $providerStatus): string
    {
        $normalized = strtolower(trim($providerStatus));

        return self::MAP[$normalized] ?? ShipmentStatusSlug::IN_TRANSIT;
    }

    public function getProviderStatuses(): array
    {
        return [
            ['code' => 'PENDING',           'label' => 'Pending'],
            ['code' => 'LABEL_CREATED',     'label' => 'Label Created'],
            ['code' => 'CONFIRMED',         'label' => 'Confirmed'],
            ['code' => 'PICKED_UP',         'label' => 'Picked Up'],
            ['code' => 'IN_TRANSIT',        'label' => 'In Transit'],
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
