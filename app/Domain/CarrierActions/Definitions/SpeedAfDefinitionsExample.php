<?php
// app/Domain/CarrierActions/Definitions/SpeedAfDefinitionsExample.php
// Pattern reference for adding future carriers. Not registered by default.

namespace App\Domain\CarrierActions\Definitions;

use App\Domain\CarrierActions\Contracts\CarrierDefinitionSet;
use App\Domain\CarrierActions\ValueObjects\ActionDefinition;
use App\Domain\CarrierActions\ValueObjects\CredentialDefinition;
use App\Domain\CarrierActions\ValueObjects\FieldDefinition;

final class SpeedAfDefinitionsExample implements CarrierDefinitionSet
{
    public function define(): array
    {
        return [
            new ActionDefinition(
                key: 'createOrder',
                label: 'Create Order',
                category: ActionDefinition::CATEGORY_MAIN_ACTION,
                method: 'POST',
                supportsAutoCreate: true,
                credentials: [
                    new CredentialDefinition('api_token', 'API Token', 'password', true),
                ],
                fields: [
                    new FieldDefinition('receiver_name', 'Receiver Name', 'text', true),
                    new FieldDefinition('receiver_phone', 'Receiver Phone', 'text', true),
                    new FieldDefinition('weight_kg', 'Weight (kg)', 'number', true, 1),
                    new FieldDefinition('cod_amount', 'COD Amount', 'number', false),
                    new FieldDefinition('service_type', 'Service Type', 'select', true, 'STANDARD', ['STANDARD', 'EXPRESS']),
                    new FieldDefinition('fragile', 'Fragile', 'boolean', false, false),
                ],
            ),
            new ActionDefinition(
                key: 'getRegions',
                label: 'Get Regions',
                category: ActionDefinition::CATEGORY_PROVINCE_CITY,
                method: 'GET',
            ),
            new ActionDefinition(
                key: 'trackOrder',
                label: 'Track Order',
                category: ActionDefinition::CATEGORY_LOOKUP,
                method: 'GET',
            ),
            new ActionDefinition(
                key: 'webhook_speedaf',
                label: 'webhook_speedaf',
                category: ActionDefinition::CATEGORY_WEBHOOK,
                method: 'WEBHOOK',
            ),
        ];
    }
}