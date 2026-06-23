<?php

namespace App\Application\Delivery\DeliveryCompany\Actions;

use App\Domain\Delivery\DeliveryCompany\Repositories\CarrierConfigurationRepositoryInterface;
use App\Infrastructure\Delivery\Carriers\CarrierManager;
use Illuminate\Support\Facades\Http;

final class TestCarrierConnectionAction
{
    public function __construct(
        private readonly CarrierManager $carrierManager,
        private readonly CarrierConfigurationRepositoryInterface $configurations,
    ) {}

    public function execute(int $deliveryCompanyId, ?int $teamId = null): bool
    {
        try {
            $credentials = $this->configurations->getCredentialsForCarrier($deliveryCompanyId, $teamId);
            $apiUrl = rtrim($credentials['api_url'] ?? '', '/');

            if (! $apiUrl || empty($credentials['api_key'])) {
                return false;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $credentials['api_key'],
            ])->get($apiUrl . '/test');

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
