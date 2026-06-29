<?php

namespace App\Infrastructure\Delivery\Carriers\Cathedis;

use App\Domain\Delivery\Shipment\Services\ShipmentStatusMapperInterface;
use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;

final class CathedisStatusMapper implements ShipmentStatusMapperInterface
{
    private const MAP = [
        'pending'           => ShipmentStatusSlug::LABEL_CREATED,
        'created'           => ShipmentStatusSlug::LABEL_CREATED,
        'label_created'     => ShipmentStatusSlug::LABEL_CREATED,
        'confirmed'         => ShipmentStatusSlug::CONFIRMED,
        'picked_up'         => ShipmentStatusSlug::IN_TRANSIT,
        'collected'         => ShipmentStatusSlug::IN_TRANSIT,
        'in_transit'        => ShipmentStatusSlug::IN_TRANSIT,
        'transport'         => ShipmentStatusSlug::IN_TRANSIT,
        'out_for_delivery'  => ShipmentStatusSlug::OUT_FOR_DELIVERY,
        'livraison'         => ShipmentStatusSlug::OUT_FOR_DELIVERY,
        'delivered'         => ShipmentStatusSlug::DELIVERED,
        'livre'             => ShipmentStatusSlug::DELIVERED,
        'received'          => ShipmentStatusSlug::FULFILLED,
        'recu'              => ShipmentStatusSlug::FULFILLED,
        'attempted_delivery'=> ShipmentStatusSlug::ATTEMPTED_DELIVERY,
        'no_answer'         => ShipmentStatusSlug::ATTEMPTED_DELIVERY,
        'absent'            => ShipmentStatusSlug::ATTEMPTED_DELIVERY,
        'reporte'           => ShipmentStatusSlug::DELAYED,
        'postponed'         => ShipmentStatusSlug::DELAYED,
        'delayed'           => ShipmentStatusSlug::DELAYED,
        'retard'            => ShipmentStatusSlug::DELAYED,
        'returned'          => ShipmentStatusSlug::RETURNED,
        'retourne'          => ShipmentStatusSlug::RETURNED,
        'failed'            => ShipmentStatusSlug::DELIVERY_FAILED,
        'delivery_failed'   => ShipmentStatusSlug::DELIVERY_FAILED,
        'echec'             => ShipmentStatusSlug::DELIVERY_FAILED,
        'annule'            => ShipmentStatusSlug::DELIVERY_FAILED,
        'cancelled'         => ShipmentStatusSlug::DELIVERY_FAILED,
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
            ['code' => 'CREATED',           'label' => 'Created'],
            ['code' => 'CONFIRMED',         'label' => 'Confirmed'],
            ['code' => 'PICKED_UP',         'label' => 'Picked Up'],
            ['code' => 'COLLECTED',         'label' => 'Collected'],
            ['code' => 'IN_TRANSIT',        'label' => 'In Transit'],
            ['code' => 'TRANSPORT',         'label' => 'Transport'],
            ['code' => 'OUT_FOR_DELIVERY',  'label' => 'Out for Delivery'],
            ['code' => 'LIVRAISON',         'label' => 'Livraison'],
            ['code' => 'DELIVERED',         'label' => 'Delivered'],
            ['code' => 'LIVRE',             'label' => 'Livré'],
            ['code' => 'RECEIVED',          'label' => 'Received'],
            ['code' => 'RECU',              'label' => 'Reçu'],
            ['code' => 'ATTEMPTED_DELIVERY','label' => 'Attempted Delivery'],
            ['code' => 'NO_ANSWER',         'label' => 'No Answer'],
            ['code' => 'ABSENT',            'label' => 'Absent'],
            ['code' => 'PORTE',             'label' => 'Reported'],
            ['code' => 'POSTPONED',         'label' => 'Postponed'],
            ['code' => 'DELAYED',           'label' => 'Delayed'],
            ['code' => 'RETARD',            'label' => 'Retard'],
            ['code' => 'RETURNED',          'label' => 'Returned'],
            ['code' => 'RETOURNE',          'label' => 'Retourné'],
            ['code' => 'FAILED',            'label' => 'Failed'],
            ['code' => 'DELIVERY_FAILED',   'label' => 'Delivery Failed'],
            ['code' => 'ECHEC',             'label' => 'Échec'],
            ['code' => 'ANNULE',            'label' => 'Annulé'],
            ['code' => 'CANCELLED',         'label' => 'Cancelled'],
        ];
    }
}
