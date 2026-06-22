<?php

namespace App\Domain\CarrierActions\Definitions;

use App\Domain\CarrierActions\Contracts\CarrierDefinitionSet;
use App\Domain\CarrierActions\ValueObjects\ActionDefinition;
use App\Domain\CarrierActions\ValueObjects\CredentialDefinition;
use App\Domain\CarrierActions\ValueObjects\FieldDefinition;

final class SenditActionDefinitions implements CarrierDefinitionSet
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
                    new CredentialDefinition('public_key', 'Public Key', 'text', true),
                    new CredentialDefinition('secret_key', 'Secret Key', 'password', true),
                ],
            ),
        ];
    }
}
