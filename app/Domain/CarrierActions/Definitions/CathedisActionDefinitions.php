<?php 

namespace App\Domain\CarrierActions\Definitions;

use App\Domain\CarrierActions\Contracts\CarrierDefinitionSet;
use App\Domain\CarrierActions\ValueObjects\ActionDefinition;
use App\Domain\CarrierActions\ValueObjects\CredentialDefinition;

final class CathedisActionDefinitions implements CarrierDefinitionSet
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
                    new CredentialDefinition('username', 'Username', 'text', true),
                    new CredentialDefinition('password', 'Password', 'password', true),
                ],
            ),
        ];
    }
}