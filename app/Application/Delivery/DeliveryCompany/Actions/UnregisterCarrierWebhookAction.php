<?php

namespace App\Application\Delivery\DeliveryCompany\Actions;

use App\Application\Delivery\DeliveryCompany\Commands\UnregisterCarrierWebhookCommand;
use App\Domain\Delivery\DeliveryCompany\Repositories\DeliveryCompanyRepositoryInterface;

final class UnregisterCarrierWebhookAction
{
    public function __construct(
        private readonly DeliveryCompanyRepositoryInterface $companies,
    ) {}

    public function execute(UnregisterCarrierWebhookCommand $command): void
    {
        $this->companies->updateWebhookState($command->deliveryCompanyId, false);
    }
}
