<?php
// app/Application/CarrierActions/Commands/SaveActionConfigHandler.php

namespace App\Application\CarrierActions\Commands;

use App\Domain\Companies\Contracts\DeliveryCompanyRepositoryInterface;
use App\Domain\Companies\Contracts\CarrierConfigurationRepositoryInterface;
use Illuminate\Support\Facades\Crypt;

final class SaveActionConfigHandler
{
    public function __construct(
        private readonly DeliveryCompanyRepositoryInterface $companyRepo,
        private readonly CarrierConfigurationRepositoryInterface $configRepo,
    ) {}

    public function handle(SaveActionConfigCommand $command): void
    {
        $company = $this->companyRepo->findOrFail($command->companyId);

        $config = $this->configRepo->findOrCreateByTeamAndCompany($command->teamId, $company->id);

        $mapping = $config->field_mapping_json ?? [];
        $actionMapping = $mapping[$command->actionKey] ?? [];

        if (array_key_exists('prefilled', $command->payload)) {
            $actionMapping['prefilled'] = array_merge(
                $actionMapping['prefilled'] ?? [],
                $command->payload['prefilled']
            );
        }

        if (array_key_exists('hidden', $command->payload)) {
            $actionMapping['hidden'] = array_merge(
                $actionMapping['hidden'] ?? [],
                $command->payload['hidden']
            );
        }

        $mapping[$command->actionKey] = $actionMapping;

        $credentials = $config->credentials_json ?? [];
        if (array_key_exists('credentials', $command->payload)) {
            $existing = $credentials[$command->actionKey] ?? [];
            foreach ($command->payload['credentials'] as $key => $value) {
                if ($value === null || $value === '') {
                    continue; // keep existing stored credential if blank submitted
                }
                $existing[$key] = Crypt::encryptString((string) $value);
            }
            $credentials[$command->actionKey] = $existing;
        }

        $updates = [
            'field_mapping_json' => $mapping,
            'credentials_json' => $credentials,
        ];

        if (array_key_exists('auto_create_enabled', $command->payload)) {
            $updates['auto_create_parcel'] = (bool) $command->payload['auto_create_enabled'];
        }

        $this->configRepo->update($config->id, $updates);
    }
}