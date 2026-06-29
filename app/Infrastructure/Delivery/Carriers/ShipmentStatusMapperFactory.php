<?php

namespace App\Infrastructure\Delivery\Carriers;

use App\Domain\Delivery\Shipment\Services\ShipmentStatusMapperInterface;
use App\Infrastructure\Delivery\Carriers\Ameex\AmeexStatusMapper;
use App\Infrastructure\Delivery\Carriers\Cathedis\CathedisStatusMapper;
use App\Infrastructure\Delivery\Carriers\ChronoDiali\ChronoDialiStatusMapper;
use App\Infrastructure\Delivery\Carriers\Generic\GenericStatusMapper;
use App\Infrastructure\Delivery\Carriers\Ozon\OzonStatusMapper;

final class ShipmentStatusMapperFactory
{
    public function make(string $carrierSlug): ShipmentStatusMapperInterface
    {
        return match (strtolower($carrierSlug)) {
            'ameex'                  => new AmeexStatusMapper(),
            'cathedis'               => new CathedisStatusMapper(),
            'ozon'                   => new OzonStatusMapper(),
            'chrono_diali',
            'chronodiali'            => new ChronoDialiStatusMapper(),
            'generic', ''            => new GenericStatusMapper(),
            default                  => new GenericStatusMapper(),
        };
    }
}
