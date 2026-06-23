<?php
// app/Domain/CarrierActions/Contracts/CarrierDefinitionSet.php

namespace App\Domain\CarrierActions\Contracts;

interface CarrierDefinitionSet
{
    /**
     * @return \App\Domain\CarrierActions\ValueObjects\ActionDefinition[]
     */
    public function define(): array;
}