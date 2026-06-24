<?php


namespace App\Domain\CarrierActions\Contracts;

interface CarrierActionDefinitionProvider
{

    public function definitionsFor(string $carrierSlug): array;
}