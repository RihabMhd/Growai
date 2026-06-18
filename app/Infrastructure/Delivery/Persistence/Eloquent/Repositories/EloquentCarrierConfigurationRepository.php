<?php

namespace App\Infrastructure\Delivery\Persistence\Eloquent\Repositories;

use App\Domain\Delivery\DeliveryCompany\Entities\CarrierConfiguration;
use App\Domain\Delivery\DeliveryCompany\Repositories\CarrierConfigurationRepositoryInterface;
use App\Infrastructure\Delivery\Persistence\Eloquent\Mappers\DeliveryMapper;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\CarrierConfigurationModel;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\DeliveryCompanyModel;

final class EloquentCarrierConfigurationRepository implements CarrierConfigurationRepositoryInterface
{
    public function __construct(
        private readonly DeliveryMapper $mapper,
    ) {}

    public function findForTeamAndCarrier(int $teamId, int $deliveryCompanyId): ?CarrierConfiguration
    {
        $model = CarrierConfigurationModel::where('team_id', $teamId)
            ->where('delivery_company_id', $deliveryCompanyId)
            ->first();

        return $model ? $this->mapper->toCarrierConfigurationEntity($model) : null;
    }

    public function save(CarrierConfiguration $configuration): CarrierConfiguration
    {
        $model = CarrierConfigurationModel::updateOrCreate(
            [
                'team_id' => $configuration->teamId,
                'delivery_company_id' => $configuration->deliveryCompanyId,
            ],
            [
                'credentials_json' => $configuration->credentials,
                'field_mapping_json' => $configuration->fieldMapping,
                'auto_create_parcel' => $configuration->autoCreateParcel,
                'webhook_enabled' => $configuration->webhookEnabled,
            ],
        );

        return $this->mapper->toCarrierConfigurationEntity($model);
    }

    public function getCredentialsForCarrier(int $deliveryCompanyId, ?int $teamId = null): array
    {
        if ($teamId) {
            $config = $this->findForTeamAndCarrier($teamId, $deliveryCompanyId);
            if ($config && ! empty($config->credentials)) {
                return $config->credentials;
            }
        }

        $company = DeliveryCompanyModel::find($deliveryCompanyId);

        if (! $company) {
            return [];
        }

        $credentials = [
            'api_url' => $company->api_url,
            'api_key' => $company->api_key ? decrypt($company->api_key) : null,
        ];

        if ($company->credentials) {
            $stored = json_decode($company->credentials, true) ?? [];
            foreach ($stored as $key => $value) {
                $credentials[$key] = $value ? decrypt($value) : null;
            }
        }

        return $credentials;
    }
}
