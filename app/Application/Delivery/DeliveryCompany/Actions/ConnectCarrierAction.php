<?php

namespace App\Application\Delivery\DeliveryCompany\Actions;

use App\Application\Delivery\DeliveryCompany\Commands\ConnectCarrierCommand;
use App\Domain\Delivery\DeliveryCompany\Entities\CarrierConfiguration;
use App\Domain\Delivery\DeliveryCompany\Exceptions\DeliveryCompanyNotFoundException;
use App\Domain\Delivery\DeliveryCompany\Repositories\CarrierConfigurationRepositoryInterface;
use App\Domain\Delivery\DeliveryCompany\Repositories\DeliveryCompanyRepositoryInterface;

final class ConnectCarrierAction
{
    public function __construct(
        private readonly DeliveryCompanyRepositoryInterface $companies,
        private readonly CarrierConfigurationRepositoryInterface $configurations,
    ) {}

    public function execute(ConnectCarrierCommand $command): CarrierConfiguration
    {
        $company = $this->companies->findById($command->deliveryCompanyId);

        if (! $company) {
            throw DeliveryCompanyNotFoundException::withId($command->deliveryCompanyId);
        }

        // use first credential as api_key sentinel to avoid schema migration
        $apiKeySentinel = array_values($command->credentials)[0] ?? null;

        // encrypt all generic credentials
        $encryptedCredentials = array_map(
            fn(string $value): string => encrypt($value),
            $command->credentials,
        );

        $this->companies->updateConnection(
            id: $command->deliveryCompanyId,
            apiKey: $apiKeySentinel !== null ? encrypt($apiKeySentinel) : null,
            credentials: $encryptedCredentials,
            isActive: true,
        );

        return $this->configurations->save(new CarrierConfiguration(
            id: null,
            teamId: $command->teamId,
            deliveryCompanyId: $command->deliveryCompanyId,
            credentials: $command->credentials,
            fieldMapping: $command->fieldMapping ?? [],
            autoCreateParcel: false,
            webhookEnabled: false,
        ));
    }
}