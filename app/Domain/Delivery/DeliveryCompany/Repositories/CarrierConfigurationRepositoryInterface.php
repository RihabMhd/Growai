<?php

namespace App\Domain\Delivery\DeliveryCompany\Repositories;

use App\Domain\Delivery\DeliveryCompany\Entities\CarrierConfiguration;

interface CarrierConfigurationRepositoryInterface
{
    public function findForTeamAndCarrier(int $teamId, int $deliveryCompanyId): ?CarrierConfiguration;

    public function save(CarrierConfiguration $configuration): CarrierConfiguration;

    public function getCredentialsForCarrier(int $deliveryCompanyId, ?int $teamId = null): array;
}
