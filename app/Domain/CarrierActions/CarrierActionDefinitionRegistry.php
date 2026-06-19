<?php
// app/Domain/CarrierActions/CarrierActionDefinitionRegistry.php

namespace App\Domain\CarrierActions;

use App\Domain\CarrierActions\Contracts\CarrierActionDefinitionProvider;
use App\Domain\CarrierActions\Definitions\AmeexActionDefinitions;
use Illuminate\Container\Container;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CarrierActionDefinitionRegistry implements CarrierActionDefinitionProvider
{
    /**
     * Map of delivery_companies.slug => CarrierDefinitionSet class.
     * Add a new carrier here only — no other code changes required.
     *
     * @var array<string, class-string<\App\Domain\CarrierActions\Contracts\CarrierDefinitionSet>>
     */
    private array $providers = [
        'ameex' => AmeexActionDefinitions::class,
        // 'speedaf' => SpeedAfDefinitionsExample::class,
    ];

    public function __construct(private readonly Container $container) {}

    public function definitionsFor(string $carrierSlug): array
    {
        $class = $this->providers[$carrierSlug] ?? null;

        if ($class === null) {
            throw new NotFoundHttpException("No action schema registered for carrier [{$carrierSlug}].");
        }

        /** @var \App\Domain\CarrierActions\Contracts\CarrierDefinitionSet $definitionSet */
        $definitionSet = $this->container->make($class);

        return $definitionSet->define();
    }
}