<?php

namespace App\Infrastructure\Delivery\Carriers;

use App\Domain\Delivery\DeliveryCompany\Repositories\CarrierConfigurationRepositoryInterface;
use App\Domain\Delivery\DeliveryCompany\Repositories\DeliveryCompanyRepositoryInterface;
use App\Infrastructure\Delivery\Carriers\Contracts\CarrierInterface;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\DeliveryCompanyModel;

final class CarrierManager
{
    public function __construct(
        private readonly CarrierFactory $factory,
        private readonly DeliveryCompanyRepositoryInterface $companies,
        private readonly CarrierConfigurationRepositoryInterface $configurations,
    ) {}

    public function resolve(int $deliveryCompanyId, ?int $teamId = null, ?string $host = null): CarrierInterface
    {
        $company = $this->companies->findById($deliveryCompanyId);

        if (! $company) {
            throw new \RuntimeException("Delivery company [{$deliveryCompanyId}] not found.");
        }

        $credentials = $this->configurations->getCredentialsForCarrier($deliveryCompanyId, $teamId);
        $webhookUrl = $host ? "https://{$host}/api/shipments/webhook/{$deliveryCompanyId}" : null;

        return $this->factory->make($company->slug, $credentials, $webhookUrl);
    }

    public function resolveFromModel(DeliveryCompanyModel $model, ?int $teamId = null, ?string $host = null): CarrierInterface
    {
        $credentials = $this->configurations->getCredentialsForCarrier($model->id, $teamId);

        if (empty($credentials['api_url'])) {
            $credentials['api_url'] = $model->api_url;
        }

        $webhookUrl = $host ? "https://{$host}/api/shipments/webhook/{$model->id}" : null;

        return $this->factory->make($model->slug ?? 'generic', $credentials, $webhookUrl);
    }
}
