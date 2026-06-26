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
        private readonly TestCarrierConnectionAction $testConnection,
    ) {}

    public function execute(ConnectCarrierCommand $command): CarrierConfiguration
    {
        $company = $this->companies->findById($command->deliveryCompanyId);

        if (! $company) {
            throw DeliveryCompanyNotFoundException::withId($command->deliveryCompanyId);
        }

        // use first credential as api_key sentinel to avoid schema migration
        $apiKeySentinel = array_values($command->credentials)[0] ?? null;

        // encrypt all generic credentials for persistence on the delivery_companies row
        $encryptedCredentials = array_map(
            fn(string $value): string => encrypt($value),
            $command->credentials,
        );

        // Persist carrier configuration first (needed for validation/test connectivity).
        $configuration = $this->configurations->save(new CarrierConfiguration(
            id: null,
            teamId: $command->teamId,
            deliveryCompanyId: $command->deliveryCompanyId,
            credentials: $command->credentials,
            fieldMapping: $command->fieldMapping ?? [],
            autoCreateParcel: false,
            webhookEnabled: false,
        ));

        // Business rule: is_active means "connected/configured/ready to create parcels".
        // Activation happens only if the carrier connectivity test succeeds.
        $connected = $this->testConnection->execute(
            deliveryCompanyId: $command->deliveryCompanyId,
            teamId: $command->teamId,
        );

        if ($connected) {
            $this->companies->updateConnection(
                id: $command->deliveryCompanyId,
                apiKey: $apiKeySentinel !== null ? encrypt($apiKeySentinel) : null,
                credentials: $encryptedCredentials,
                isActive: true,
            );

            return $configuration;
        }

        // Failed connection: do NOT activate and clear persisted connection credentials.
        $this->companies->updateConnection(
            id: $command->deliveryCompanyId,
            apiKey: null,
            credentials: null,
            isActive: false,
        );

        return $configuration;
    }
}
