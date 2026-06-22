<?php
// app/Application/CarrierActions/Commands/SaveActionConfigHandler.php

namespace App\Application\CarrierActions\Commands;

use App\Domain\Delivery\DeliveryCompany\Entities\CarrierConfiguration;
use App\Domain\Delivery\DeliveryCompany\Repositories\DeliveryCompanyRepositoryInterface;
use App\Domain\Delivery\DeliveryCompany\Repositories\CarrierConfigurationRepositoryInterface;
use Illuminate\Support\Facades\Crypt;

final class SaveActionConfigHandler
{
    public function __construct(
        private readonly DeliveryCompanyRepositoryInterface $companyRepo,
        private readonly CarrierConfigurationRepositoryInterface $configRepo,
    ) {}

    public function handle(SaveActionConfigCommand $command): void
    {
        $company = $this->companyRepo->findById($command->companyId);

        $config = $this->configRepo->findForTeamAndCarrier(
            $command->teamId,
            $company->id
        );

        $mapping = $config->fieldMapping;
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

        $credentials = $config->credentials;
        if (array_key_exists('credentials', $command->payload)) {
            $existing = $credentials[$command->actionKey] ?? [];
            foreach ($command->payload['credentials'] as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $existing[$key] = Crypt::encryptString((string) $value);
            }
            $credentials[$command->actionKey] = $existing;
        }

        $autoCreateParcel = array_key_exists('auto_create_enabled', $command->payload)
            ? (bool) $command->payload['auto_create_enabled']
            : $config->autoCreateParcel;

        $updatedConfig = new CarrierConfiguration(
            id: $config->id,
            teamId: $config->teamId,
            deliveryCompanyId: $config->deliveryCompanyId,
            credentials: $credentials,
            fieldMapping: $mapping,
            autoCreateParcel: $autoCreateParcel,
            webhookEnabled: $config->webhookEnabled,
        );

        $this->configRepo->save($updatedConfig);
    }
}
