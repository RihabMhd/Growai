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

        $encryptedCredentials = [
            'api_key' => encrypt($command->apiKey),
            'api_secret' => $command->apiSecret ? encrypt($command->apiSecret) : null,
            'username' => $command->username ? encrypt($command->username) : null,
            'password' => $command->password ? encrypt($command->password) : null,
        ];

        $this->companies->updateConnection(
            id: $command->deliveryCompanyId,
            apiKey: encrypt($command->apiKey),
            credentials: $encryptedCredentials,
            isActive: true,
        );

        return $this->configurations->save(new CarrierConfiguration(
            id: null,
            teamId: $command->teamId,
            deliveryCompanyId: $command->deliveryCompanyId,
            credentials: [
                'api_key' => $command->apiKey,
                'api_secret' => $command->apiSecret,
                'username' => $command->username,
                'password' => $command->password,
            ],
            fieldMapping: $command->fieldMapping ?? [],
            autoCreateParcel: false,
            webhookEnabled: false,
        ));
    }
}
