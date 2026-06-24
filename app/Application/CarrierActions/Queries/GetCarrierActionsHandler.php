<?php


namespace App\Application\CarrierActions\Queries;

use App\Application\CarrierActions\DTOs\ActionResponseDTO;
use App\Domain\CarrierActions\Contracts\CarrierActionDefinitionProvider;
use App\Domain\CarrierActions\ValueObjects\ActionDefinition;
use App\Domain\Delivery\DeliveryCompany\Repositories\CarrierConfigurationRepositoryInterface;
use App\Domain\Delivery\DeliveryCompany\Repositories\DeliveryCompanyRepositoryInterface;
use Illuminate\Support\Facades\Crypt;

final class GetCarrierActionsHandler
{
    public function __construct(
        private readonly CarrierActionDefinitionProvider $definitions,
        private readonly DeliveryCompanyRepositoryInterface $companyRepo,
        private readonly CarrierConfigurationRepositoryInterface $configRepo,
    ) {}


    public function handle(GetCarrierActionsQuery $query): array
    {
        $company = $this->companyRepo->findById($query->companyId);

        abort_unless($company, 404, 'Delivery company not found');

        $config = $this->configRepo->findForTeamAndCarrier($query->teamId, $company->id);

        $credentialsJson = $config?->credentials ?? [];
        $mappingJson = $config?->fieldMapping ?? [];

        $definitions = $this->definitions->definitionsFor($company->slug);
        $companyId = $company->id;

        return array_map(
            fn (ActionDefinition $def) => $this->mapToDto($def, $credentialsJson, $mappingJson, $config, $companyId),
            $definitions
        );
    }

    private function mapToDto(ActionDefinition $def, array $credentialsJson, array $mappingJson, $config, int $companyId): ActionResponseDTO
    {
        $savedAction = $mappingJson[$def->key] ?? [];
        $savedHidden = $savedAction['hidden'] ?? [];
        $savedPrefilled = $savedAction['prefilled'] ?? [];

        if ($def->category === ActionDefinition::CATEGORY_WEBHOOK) {
            $savedPrefilled['url'] = url("/api/shipments/webhook/{$companyId}");
        }
        $savedCredentialsRaw = $credentialsJson[$def->key] ?? [];

        if (empty($savedCredentialsRaw) && $credentialsJson && $def->credentials) {
            $savedCredentialsRaw = $credentialsJson;
        }

        $requiredFields = array_filter($def->fields, fn($f) => $f->required);
        $configuredCount = 0;
        $missing = [];
        foreach ($requiredFields as $f) {
            $hasValue = (
                (isset($savedPrefilled[$f->key]) && $savedPrefilled[$f->key] !== '' && $savedPrefilled[$f->key] !== null)
                || ($savedHidden[$f->key] ?? false)
                || $f->default !== null
            );
            if ($hasValue) {
                $configuredCount++;
            } else {
                $missing[] = $f->key;
            }
        }
        $totalRequired = count($requiredFields);
        $completionPercent = $totalRequired > 0 ? (int) round(($configuredCount / $totalRequired) * 100) : 100;

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
            savedCredentials: $this->decryptCredentials($savedCredentialsRaw),
            savedPrefilled: $savedPrefilled,
            savedHidden: $savedHidden,
            autoCreateEnabled: $def->supportsAutoCreate
                ? (bool) ($config?->autoCreateParcel ?? false)
                : null,
            testStatus: $savedAction['test_status'] ?? 'pending',
            lastResponse: $savedAction['last_response'] ?? null,
            lastError: $savedAction['last_error'] ?? null,
            requiredFields: $totalRequired,
            configuredFields: $configuredCount,
            missingFields: $missing,
            completionPercent: $completionPercent,
            readyForAutoCreate: $completionPercent === 100,
            supportsApiRegistration: $def->supportsApiRegistration,
        );
    }

    // decrypt credentials for frontend display
    private function decryptCredentials(array $raw): array
    {
        $decrypted = [];
        foreach ($raw as $key => $value) {
            if (is_string($value) && $value !== '') {
                try {
                    $decrypted[$key] = Crypt::decryptString($value);
                } catch (\Throwable) {
                    $decrypted[$key] = $value;
                }
            } else {
                $decrypted[$key] = $value;
            }
        }
        return $decrypted;
    }
}
