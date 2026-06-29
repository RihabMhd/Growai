<?php

namespace App\Infrastructure\Delivery\Carriers\ChronoDiali;

use App\Domain\Delivery\Shipment\Services\ShipmentStatusMapperInterface;

final class ChronoDialiStatusMapper implements ShipmentStatusMapperInterface
{
    private const MAP = [
        'pending'           => 'label_created',
        'creé'              => 'label_created',
        'cree'              => 'label_created',
        'label_created'     => 'label_created',
        'confirmed'         => 'confirmed',
        'confirmé'          => 'confirmed',
        'confirme'          => 'confirmed',
        'picked_up'         => 'in_transit',
        'enlevé'            => 'in_transit',
        'enleve'            => 'in_transit',
        'in_transit'        => 'in_transit',
        'en_cours'          => 'in_transit',
        'en cours'          => 'in_transit',
        'travelling'        => 'in_transit',
        'en_transit'        => 'in_transit',
        'out_for_delivery'  => 'out_for_delivery',
        'en_livraison'      => 'out_for_delivery',
        'livraison'         => 'out_for_delivery',
        'delivered'         => 'delivered',
        'livré'             => 'delivered',
        'livre'             => 'delivered',
        'received'          => 'fulfilled',
        'reçu'              => 'fulfilled',
        'recu'              => 'fulfilled',
        'attempted_delivery'=> 'attempted_delivery',
        'no_answer'         => 'attempted_delivery',
        'pas_de_reponse'    => 'attempted_delivery',
        'absent'            => 'attempted_delivery',
        'postponed'         => 'delayed',
        'reporté'           => 'delayed',
        'reporte'           => 'delayed',
        'delayed'           => 'delayed',
        'retard'            => 'delayed',
        'returned'          => 'returned',
        'retourné'          => 'returned',
        'retourne'          => 'returned',
        'failed'            => 'delivery_failed',
        'delivery_failed'   => 'delivery_failed',
        'échec'             => 'delivery_failed',
        'echec'             => 'delivery_failed',
        'annulé'            => 'delivery_failed',
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
            ['code' => 'CRÉÉ',              'label' => 'Créé'],
            ['code' => 'CONFIRMÉ',          'label' => 'Confirmé'],
            ['code' => 'ENLEVÉ',            'label' => 'Enlevé'],
            ['code' => 'IN_TRANSIT',        'label' => 'In Transit'],
            ['code' => 'EN_COURS',          'label' => 'En cours'],
            ['code' => 'EN_TRANSIT',        'label' => 'En transit'],
            ['code' => 'OUT_FOR_DELIVERY',  'label' => 'Out for Delivery'],
            ['code' => 'EN_LIVRAISON',      'label' => 'En livraison'],
            ['code' => 'DELIVERED',         'label' => 'Delivered'],
            ['code' => 'LIVRÉ',             'label' => 'Livré'],
            ['code' => 'REÇU',              'label' => 'Reçu'],
            ['code' => 'ATTEMPTED_DELIVERY','label' => 'Attempted Delivery'],
            ['code' => 'NO_ANSWER',         'label' => 'No Answer'],
            ['code' => 'PAS_DE_REPONSE',    'label' => 'Pas de réponse'],
            ['code' => 'ABSENT',            'label' => 'Absent'],
            ['code' => 'REPORTÉ',           'label' => 'Reporté'],
            ['code' => 'DELAYED',           'label' => 'Delayed'],
            ['code' => 'RETARD',            'label' => 'Retard'],
            ['code' => 'RETOURNÉ',          'label' => 'Retourné'],
            ['code' => 'FAILED',            'label' => 'Failed'],
            ['code' => 'DELIVERY_FAILED',   'label' => 'Delivery Failed'],
            ['code' => 'ÉCHEC',             'label' => 'Échec'],
            ['code' => 'ANNULÉ',            'label' => 'Annulé'],
            ['code' => 'CANCELLED',         'label' => 'Cancelled'],
        ];
    }
}
