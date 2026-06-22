<?php
// app/Application/CarrierActions/Commands/TestActionHandler.php

namespace App\Application\CarrierActions\Commands;

use App\Domain\Delivery\DeliveryCompany\Repositories\DeliveryCompanyRepositoryInterface;
use App\Domain\Delivery\DeliveryCompany\Repositories\CarrierConfigurationRepositoryInterface;
use App\Domain\CarrierActions\Contracts\CarrierActionDefinitionProvider;
use App\Infrastructure\Carriers\Contracts\CarrierHttpClientFactory;
use Throwable;

final class TestActionHandler
{
    public function __construct(
        private readonly DeliveryCompanyRepositoryInterface $companyRepo,
        private readonly CarrierConfigurationRepositoryInterface $configRepo,
        private readonly CarrierActionDefinitionProvider $definitions,
        private readonly CarrierHttpClientFactory $clientFactory,
    ) {}

    public function handle(TestActionCommand $command): array
    {
        $company = $this->companyRepo->findOrFail($command->companyId);
        $config = $this->configRepo->findOrCreateByTeamAndCompany($command->teamId, $company->id);

        $actionDef = collect($this->definitions->definitionsFor($company->slug))
            ->firstWhere('key', $command->actionKey);

        abort_unless($actionDef, 404, 'Unknown action');

        $client = $this->clientFactory->forCarrier($company->slug, $config);

        $mapping = $config->field_mapping_json ?? [];
        $actionMapping = $mapping[$command->actionKey] ?? [];

        try {
            $response = $client->call(
                $actionDef->key,
                $actionDef->method,
                $actionMapping['prefilled'] ?? []
            );

            $actionMapping['test_status'] = 'passed';
            $actionMapping['last_response'] = $response;
            $actionMapping['last_error'] = null;
            $result = ['ok' => true, 'response' => $response];
        } catch (Throwable $e) {
            $actionMapping['test_status'] = 'failed';
            $actionMapping['last_error'] = $e->getMessage();
            $result = ['ok' => false, 'error' => $e->getMessage()];
        }

        $mapping[$command->actionKey] = $actionMapping;
        $this->configRepo->update($config->id, ['field_mapping_json' => $mapping]);

        return $result;
    }
}