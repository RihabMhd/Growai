<?php
// app/Domain/CarrierActions/Contracts/CarrierActionDefinitionProvider.php

namespace App\Domain\CarrierActions\Contracts;

interface CarrierActionDefinitionProvider
{
    /**
     * @return \App\Domain\CarrierActions\ValueObjects\ActionDefinition[]
     */
    public function definitionsFor(string $carrierSlug): array;
}