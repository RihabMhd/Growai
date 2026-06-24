<?php


namespace App\Application\CarrierActions\Commands;

use App\Domain\Delivery\DeliveryCompany\Repositories\DeliveryCompanyRepositoryInterface;
use App\Domain\Delivery\DeliveryCompany\Repositories\CarrierConfigurationRepositoryInterface;
use App\Domain\Delivery\DeliveryCompany\Entities\CarrierConfiguration;
use App\Domain\CarrierActions\Contracts\CarrierActionDefinitionProvider;
use App\Domain\CarrierActions\ValueObjects\ActionDefinition;
use App\Infrastructure\Carriers\Contracts\CarrierHttpClientFactory;
use Throwable;

final class RegisterWebhookHandler
{
    public function __construct(
        private readonly DeliveryCompanyRepositoryInterface $companyRepo,
        private readonly CarrierConfigurationRepositoryInterface $configRepo,
        private readonly CarrierActionDefinitionProvider $definitions,
        private readonly CarrierHttpClientFactory $clientFactory,
    ) {}

    public function handle(RegisterWebhookCommand $command): array
    {
        $company = $this->companyRepo->findById($command->companyId);
        $config = $this->configRepo->findForTeamAndCarrier(
            $command->teamId,
            $company->id
        );

        $webhookAction = collect($this->definitions->definitionsFor($company->slug))
            ->first(fn(ActionDefinition $a) => $a->category === ActionDefinition::CATEGORY_WEBHOOK);

        abort_unless($webhookAction, 404, 'No webhook action defined for carrier');

        $url = url("/api/shipments/webhook/{$company->id}");

        $mapping = $config->fieldMapping;
        $webhookMapping = $mapping[$webhookAction->key] ?? [];
        $webhookMapping['url'] = $url;

        if (! $webhookAction->supportsApiRegistration) {
            $webhookMapping['registered'] = true;
            $webhookMapping['last_response'] = null;
            $webhookMapping['last_error'] = null;
            $result = ['ok' => true, 'url' => $url, 'manual' => true];
        } else {
            $client = $this->clientFactory->forCarrier($company->slug, $config);

            try {
                $response = $client->registerWebhook($url);
                $webhookMapping['registered'] = true;
                $webhookMapping['last_response'] = $response;
                $webhookMapping['last_error'] = null;
                $result = ['ok' => true, 'url' => $url, 'response' => $response];
            } catch (Throwable $e) {
                $webhookMapping['registered'] = false;
                $webhookMapping['last_error'] = $e->getMessage();
                $result = ['ok' => false, 'url' => $url, 'error' => $e->getMessage()];
            }
        }

        $mapping[$webhookAction->key] = $webhookMapping;

        $updatedConfig = new CarrierConfiguration(
            id: $config->id,
            teamId: $config->teamId,
            deliveryCompanyId: $config->deliveryCompanyId,
            credentials: $config->credentials,
            fieldMapping: $mapping,
            autoCreateParcel: $config->autoCreateParcel,
            webhookEnabled: $webhookMapping['registered'] ?? false,
        );

        $this->configRepo->save($updatedConfig);

        return $result;
    }
}
