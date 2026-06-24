<?php


namespace App\Domain\CarrierActions;

use App\Domain\CarrierActions\Contracts\CarrierActionDefinitionProvider;
use App\Domain\CarrierActions\Definitions\AmeexActionDefinitions;
use App\Domain\CarrierActions\Definitions\CathedisActionDefinitions;
use App\Domain\CarrierActions\Definitions\ChronoDialiActionDefinitions;
use App\Domain\CarrierActions\Definitions\SenditActionDefinitions;
use App\Domain\CarrierActions\Definitions\OzonActionDefinitions;
use Illuminate\Container\Container;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CarrierActionDefinitionRegistry implements CarrierActionDefinitionProvider
{
    // register new carriers here to avoid changing other code
    private array $providers = [
        'ameex' => AmeexActionDefinitions::class,
        'cathedis' => CathedisActionDefinitions::class,
        'chrono_diali' => ChronoDialiActionDefinitions::class,
        'ozon' => OzonActionDefinitions::class,
        'sendit' => SenditActionDefinitions::class,

    ];

    public function __construct(private readonly Container $container) {}

    public function definitionsFor(string $carrierSlug): array
    {
        $class = $this->providers[$carrierSlug] ?? null;

        if ($class === null) {
            throw new NotFoundHttpException("No action schema registered for carrier [{$carrierSlug}].");
        }


        $definitionSet = $this->container->make($class);

        return $definitionSet->define();
    }
}
