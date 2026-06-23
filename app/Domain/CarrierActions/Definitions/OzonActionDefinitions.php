<?php

namespace App\Domain\CarrierActions\Definitions;

use App\Domain\CarrierActions\Contracts\CarrierDefinitionSet;
use App\Domain\CarrierActions\ValueObjects\ActionDefinition;
use App\Domain\CarrierActions\ValueObjects\CredentialDefinition;
use App\Domain\CarrierActions\ValueObjects\FieldDefinition;

final class OzonActionDefinitions implements CarrierDefinitionSet
{
    public function define(): array
    {
        return [
            new ActionDefinition(
                key: 'connect',
                label: 'Connect',
                category: ActionDefinition::CATEGORY_MAIN_ACTION,
                method: 'POST',
                credentials: [
                    new CredentialDefinition('partner_id', 'Partner ID', 'text', true),
                    new CredentialDefinition('api_key', 'API Key', 'password', true),
                ],
            ),
        ];
    }
}
