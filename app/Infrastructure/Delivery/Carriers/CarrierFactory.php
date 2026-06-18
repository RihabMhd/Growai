<?php

namespace App\Infrastructure\Delivery\Carriers;

use App\Infrastructure\Delivery\Carriers\Ameex\AmeexConnector;
use App\Infrastructure\Delivery\Carriers\Cathedis\CathedisConnector;
use App\Infrastructure\Delivery\Carriers\ChronoDiali\ChronoDialiConnector;
use App\Infrastructure\Delivery\Carriers\Contracts\CarrierInterface;
use App\Infrastructure\Delivery\Carriers\Generic\GenericCarrierConnector;
use App\Infrastructure\Delivery\Carriers\Ozon\OzonConnector;
use InvalidArgumentException;

final class CarrierFactory
{
    public function make(string $carrierSlug, array $credentials, ?string $webhookUrl = null): CarrierInterface
    {
        return match (strtolower($carrierSlug)) {
            'ameex' => new AmeexConnector($credentials, $webhookUrl),
            'cathedis' => new CathedisConnector($credentials, $webhookUrl),
            'ozon' => new OzonConnector($credentials, $webhookUrl),
            'chrono_diali', 'chronodiali' => new ChronoDialiConnector($credentials, $webhookUrl),
            'generic', '' => new GenericCarrierConnector($credentials, $webhookUrl),
            default => throw new InvalidArgumentException("Unsupported carrier slug [{$carrierSlug}]."),
        };
    }
}
