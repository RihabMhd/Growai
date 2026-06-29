<?php

namespace App\Infrastructure\Delivery\Carriers\Cathedis;

use App\Domain\Delivery\Shipment\Services\ShipmentStatusMapperInterface;

final class CathedisStatusMapper implements ShipmentStatusMapperInterface
{
    private const MAP = [
        'pending'           => 'label_created',
        'created'           => 'label_created',
        'label_created'     => 'label_created',
        'confirmed'         => 'confirmed',
        'picked_up'         => 'in_transit',
        'collected'         => 'in_transit',
        'in_transit'        => 'in_transit',
        'transport'         => 'in_transit',
        'out_for_delivery'  => 'out_for_delivery',
        'livraison'         => 'out_for_delivery',
        'delivered'         => 'delivered',
        'livre'             => 'delivered',
        'received'          => 'fulfilled',
        'recu'              => 'fulfilled',
        'attempted_delivery'=> 'attempted_delivery',
        'no_answer'         => 'attempted_delivery',
        'absent'            => 'attempted_delivery',
        'reporte'           => 'delayed',
        'postponed'         => 'delayed',
        'delayed'           => 'delayed',
        'retard'            => 'delayed',
        'returned'          => 'returned',
        'retourne'          => 'returned',
        'failed'            => 'delivery_failed',
        'delivery_failed'   => 'delivery_failed',
        'echec'             => 'delivery_failed',
        'annule'            => 'delivery_failed',
        'cancelled'         => 'delivery_failed',
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
