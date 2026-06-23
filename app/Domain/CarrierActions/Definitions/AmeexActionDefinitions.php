<?php
// app/Domain/CarrierActions/Definitions/AmeexActionDefinitions.php

namespace App\Domain\CarrierActions\Definitions;

use App\Domain\CarrierActions\Contracts\CarrierDefinitionSet;
use App\Domain\CarrierActions\ValueObjects\ActionDefinition;
use App\Domain\CarrierActions\ValueObjects\CredentialDefinition;
use App\Domain\CarrierActions\ValueObjects\FieldDefinition;

final class AmeexActionDefinitions implements CarrierDefinitionSet
{
    public function define(): array
    {
        return [
            new ActionDefinition(
                key: 'createParcel',
                label: 'Create Parcel',
                category: ActionDefinition::CATEGORY_MAIN_ACTION,
                method: 'POST',
                supportsAutoCreate: true,
                credentials: [
                    new CredentialDefinition('api_id', 'C-Api-Id', 'text', true),
                    new CredentialDefinition('api_key', 'C-Api-Key', 'password', true),
                    new CredentialDefinition('secret_key', 'Secret Key', 'password', false),
                ],
                fields: [
                    new FieldDefinition('api_id', 'Api-Id', 'text', true),
                    new FieldDefinition('nom_client', 'Nom de Client', 'text', true),
                    new FieldDefinition('city', 'City', 'text', true),
                    new FieldDefinition('phone', 'Phone', 'text', true),
                    new FieldDefinition('address', 'Address', 'text', true),
                    new FieldDefinition('totale', 'Totale', 'number', true),
                    new FieldDefinition('type_livraison', 'Type de livraison', 'select', true, 'SIMPLE', ['SIMPLE', 'EXPRESS']),
                    new FieldDefinition('ouverture', 'Ouveture ?', 'boolean', true, false),
                    new FieldDefinition('tester_produit', 'Tester le Produit ?', 'boolean', true, false),
                    new FieldDefinition('fragile', 'Fragile ?', 'boolean', true, false),
                    new FieldDefinition('product', 'Product', 'text', true),
                    new FieldDefinition('echange', 'Echange ?', 'boolean', true, false),
                    new FieldDefinition('note', 'Note', 'text', false),
                ],
            ),
            new ActionDefinition(
                key: 'status',
                label: 'Status',
                category: ActionDefinition::CATEGORY_LOOKUP,
                method: 'POST',
            ),
            new ActionDefinition(
                key: 'createProductCopy',
                label: 'Create Product Copy',
                category: ActionDefinition::CATEGORY_LOOKUP,
                method: 'POST',
                credentials: [
                    new CredentialDefinition('api_id', 'C-Api-Id', 'text', true),
                    new CredentialDefinition('api_key', 'C-Api-Key', 'password', true),
                ],
                fields: [
                    new FieldDefinition('ref', 'Ref', 'text', true),
                    new FieldDefinition('name', 'Name', 'text', true),
                    new FieldDefinition('api_id', 'Api-Id', 'text', true),
                ],
            ),
            new ActionDefinition(
                key: 'getCities',
                label: 'Get Cities',
                category: ActionDefinition::CATEGORY_PROVINCE_CITY,
                method: 'GET',
            ),
            new ActionDefinition(
                key: 'webhook_ameex',
                label: 'webhook_ameex',
                category: ActionDefinition::CATEGORY_WEBHOOK,
                method: 'WEBHOOK',
                supportsApiRegistration: false,
            ),
        ];
    }
}
