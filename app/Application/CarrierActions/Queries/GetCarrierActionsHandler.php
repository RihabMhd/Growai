<?php
// app/Application/CarrierActions/Queries/GetCarrierActionsHandler.php

namespace App\Application\CarrierActions\Queries;

use App\Application\CarrierActions\DTOs\ActionResponseDTO;
use App\Domain\CarrierActions\Contracts\CarrierActionDefinitionProvider;
use App\Domain\CarrierActions\ValueObjects\ActionDefinition;
use App\Domain\Delivery\DeliveryCompany\Repositories\CarrierConfigurationRepositoryInterface;
use App\Domain\Delivery\DeliveryCompany\Repositories\DeliveryCompanyRepositoryInterface;

final class GetCarrierActionsHandler
{
    public function __construct(
        private readonly CarrierActionDefinitionProvider $definitions,
        private readonly DeliveryCompanyRepositoryInterface $companyRepo,
        private readonly CarrierConfigurationRepositoryInterface $configRepo,
    ) {}

    /**
     * @return ActionResponseDTO[]
     */
    public function handle(GetCarrierActionsQuery $query): array
    {
        $company = $this->companyRepo->findById($query->companyId);

        abort_unless($company, 404, 'Delivery company not found');

        $config = $this->configRepo->findForTeamAndCarrier($query->teamId, $company->id);

        $credentialsJson = $config?->credentials ?? [];
        $mappingJson = $config?->fieldMapping ?? [];

        $definitions = $this->definitions->definitionsFor($company->slug);

        return array_map(
            fn (ActionDefinition $def) => $this->mapToDto($def, $credentialsJson, $mappingJson, $config),
            $definitions
        );
    }

    private function mapToDto(ActionDefinition $def, array $credentialsJson, array $mappingJson, $config): ActionResponseDTO
    {
        $savedAction = $mappingJson[$def->key] ?? [];
        $savedHidden = $savedAction['hidden'] ?? [];
        $savedCredentialsRaw = $credentialsJson[$def->key] ?? [];

        return new ActionResponseDTO(
            key: $def->key,
            label: $def->label,
            category: $def->category,
            method: $def->method,
            credentials: array_map(fn ($c) => $c->toArray(), $def->credentials),
            fields: array_map(
                fn ($f) => $f->toArray($savedHidden[$f->key] ?? false),
                $def->fields
            ),
            savedCredentials: $this->maskCredentials($savedCredentialsRaw),
            savedPrefilled: $savedAction['prefilled'] ?? [],
            savedHidden: $savedHidden,
            autoCreateEnabled: $def->supportsAutoCreate
                ? (bool) ($config?->autoCreateParcel ?? false)
                : null,
            testStatus: $savedAction['test_status'] ?? 'pending',
            lastResponse: $savedAction['last_response'] ?? null,
            lastError: $savedAction['last_error'] ?? null,
        );
    }

    /**
     * Never return decrypted credential values to the frontend.
     */
    private function maskCredentials(array $raw): array
    {
        $masked = [];
        foreach ($raw as $key => $value) {
            $masked[$key] = $value ? '••••••••' : null;
        }
        return $masked;
    }
}
