<?php

namespace App\Application\Delivery\DeliveryCompany\Actions;

use App\Infrastructure\Delivery\Carriers\CarrierFactory;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\DeliveryCompanyModel;

final class GetAllDeliveryCompanyStatusesAction
{
    public function __construct(
        private readonly CarrierFactory $carrierFactory,
    ) {}

    public function execute(): array
    {
        $companies = DeliveryCompanyModel::query()
            ->where('is_active', true)
            ->get();

        $grouped = [];

        foreach ($companies as $company) {
            try {
                $carrier = $this->carrierFactory->make(
                    $company->slug ?? 'generic',
                    $company->api_url ? ['api_url' => $company->api_url] : [],
                );

                $statuses = $carrier->getAvailableStatuses();

                foreach ($statuses as $status) {
                    if (is_array($status) && isset($status['code'], $status['label'])) {
                        $code = (string) $status['code'];
                        if (!isset($grouped[$code])) {
                            $grouped[$code] = $status;
                        }
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return array_values(array_map(function (array $s): array {
            return [
                'code' => (string) ($s['code'] ?? ''),
                'label' => (string) ($s['label'] ?? ''),
                'color' => (string) ($s['color'] ?? ''),
            ];
        }, $grouped));
    }
}
