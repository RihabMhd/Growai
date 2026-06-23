<?php

namespace App\Application\Delivery\DeliveryCompany\Actions;

use App\Application\Delivery\DeliveryCompany\Commands\RegisterCarrierWebhookCommand;
use App\Domain\Delivery\DeliveryCompany\Exceptions\CarrierNotConnectedException;
use App\Domain\Delivery\DeliveryCompany\Exceptions\DeliveryCompanyNotFoundException;
use App\Domain\Delivery\DeliveryCompany\Repositories\DeliveryCompanyRepositoryInterface;
use App\Infrastructure\Delivery\Queue\Jobs\RegisterWebhookJob;

final class RegisterCarrierWebhookAction
{
    public function __construct(
        private readonly DeliveryCompanyRepositoryInterface $companies,
    ) {}

    public function execute(RegisterCarrierWebhookCommand $command): void
    {
        $company = $this->companies->findById($command->deliveryCompanyId);

        if (! $company) {
            throw DeliveryCompanyNotFoundException::withId($command->deliveryCompanyId);
        }

        if (! $company->isConnected()) {
            throw CarrierNotConnectedException::forCompany($command->deliveryCompanyId);
        }

        RegisterWebhookJob::dispatch(
            deliveryCompanyId: $command->deliveryCompanyId,
            teamId: $command->teamId,
            host: $command->host,
        );
    }
}
