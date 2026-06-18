<?php

namespace App\Application\Delivery\DeliveryCompany\Actions;

use App\Application\Delivery\DeliveryCompany\Commands\DisconnectCarrierCommand;
use App\Domain\Delivery\DeliveryCompany\Entities\CarrierConfiguration;
use App\Domain\Delivery\DeliveryCompany\Exceptions\DeliveryCompanyNotFoundException;
use App\Domain\Delivery\DeliveryCompany\Repositories\CarrierConfigurationRepositoryInterface;
use App\Domain\Delivery\DeliveryCompany\Repositories\DeliveryCompanyRepositoryInterface;

final class DisconnectCarrierAction
{
    public function __construct(
        private readonly DeliveryCompanyRepositoryInterface $companies,
        private readonly CarrierConfigurationRepositoryInterface $configurations,
    ) {}

    public function execute(DisconnectCarrierCommand $command): void
    {
        $company = $this->companies->findById($command->deliveryCompanyId);

        if (! $company) {
            throw DeliveryCompanyNotFoundException::withId($command->deliveryCompanyId);
        }

        $this->companies->updateConnection(
            id: $command->deliveryCompanyId,
            apiKey: null,
            credentials: null,
            isActive: false,
        );

        $config = $this->configurations->findForTeamAndCarrier($command->teamId, $command->deliveryCompanyId);

        if ($config) {
            $this->configurations->save(new CarrierConfiguration(
                id: $config->id,
                teamId: $config->teamId,
                deliveryCompanyId: $config->deliveryCompanyId,
                credentials: [],
                fieldMapping: $config->fieldMapping,
                autoCreateParcel: $config->autoCreateParcel,
                webhookEnabled: false,
            ));
        }
    }
}
