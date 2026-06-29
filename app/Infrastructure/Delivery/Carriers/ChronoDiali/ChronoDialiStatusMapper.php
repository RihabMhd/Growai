<?php

namespace App\Infrastructure\Delivery\Carriers\ChronoDiali;

use App\Domain\Delivery\Shipment\Services\ShipmentStatusMapperInterface;
use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;

final class ChronoDialiStatusMapper implements ShipmentStatusMapperInterface
{
    private const MAP = [
        'pending'           => ShipmentStatusSlug::LABEL_CREATED,
        'creé'              => ShipmentStatusSlug::LABEL_CREATED,
        'cree'              => ShipmentStatusSlug::LABEL_CREATED,
        'label_created'     => ShipmentStatusSlug::LABEL_CREATED,
        'confirmed'         => ShipmentStatusSlug::CONFIRMED,
        'confirmé'          => ShipmentStatusSlug::CONFIRMED,
        'confirme'          => ShipmentStatusSlug::CONFIRMED,
        'picked_up'         => ShipmentStatusSlug::IN_TRANSIT,
        'enlevé'            => ShipmentStatusSlug::IN_TRANSIT,
        'enleve'            => ShipmentStatusSlug::IN_TRANSIT,
        'in_transit'        => ShipmentStatusSlug::IN_TRANSIT,
        'en_cours'          => ShipmentStatusSlug::IN_TRANSIT,
        'en cours'          => ShipmentStatusSlug::IN_TRANSIT,
        'travelling'        => ShipmentStatusSlug::IN_TRANSIT,
        'en_transit'        => ShipmentStatusSlug::IN_TRANSIT,
        'out_for_delivery'  => ShipmentStatusSlug::OUT_FOR_DELIVERY,
        'en_livraison'      => ShipmentStatusSlug::OUT_FOR_DELIVERY,
        'livraison'         => ShipmentStatusSlug::OUT_FOR_DELIVERY,
        'delivered'         => ShipmentStatusSlug::DELIVERED,
        'livré'             => ShipmentStatusSlug::DELIVERED,
        'livre'             => ShipmentStatusSlug::DELIVERED,
        'received'          => ShipmentStatusSlug::FULFILLED,
        'reçu'              => ShipmentStatusSlug::FULFILLED,
        'recu'              => ShipmentStatusSlug::FULFILLED,
        'attempted_delivery'=> ShipmentStatusSlug::ATTEMPTED_DELIVERY,
        'no_answer'         => ShipmentStatusSlug::ATTEMPTED_DELIVERY,
        'pas_de_reponse'    => ShipmentStatusSlug::ATTEMPTED_DELIVERY,
        'absent'            => ShipmentStatusSlug::ATTEMPTED_DELIVERY,
        'postponed'         => ShipmentStatusSlug::DELAYED,
        'reporté'           => ShipmentStatusSlug::DELAYED,
        'reporte'           => ShipmentStatusSlug::DELAYED,
        'delayed'           => ShipmentStatusSlug::DELAYED,
        'retard'            => ShipmentStatusSlug::DELAYED,
        'returned'          => ShipmentStatusSlug::RETURNED,
        'retourné'          => ShipmentStatusSlug::RETURNED,
        'retourne'          => ShipmentStatusSlug::RETURNED,
        'failed'            => ShipmentStatusSlug::DELIVERY_FAILED,
        'delivery_failed'   => ShipmentStatusSlug::DELIVERY_FAILED,
        'échec'             => ShipmentStatusSlug::DELIVERY_FAILED,
        'echec'             => ShipmentStatusSlug::DELIVERY_FAILED,
        'annulé'            => ShipmentStatusSlug::DELIVERY_FAILED,
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
