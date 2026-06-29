<?php

namespace App\Application\Delivery\DeliveryCompany\Actions;

use App\Infrastructure\Delivery\Carriers\CarrierManager;

final class GetDeliveryCompanyStatusesAction
{
    public function __construct(
        private readonly CarrierManager $carrierManager,
    ) {}

    public function execute(int $deliveryCompanyId): array
    {
        $carrier = $this->carrierManager->resolve($deliveryCompanyId);

        $statuses = $carrier->getAvailableStatuses();

        return array_values(array_map(function (array $s): array {
            return [
                'code' => (string) ($s['code'] ?? ''),
                'label' => (string) ($s['label'] ?? ''),
                'color' => (string) ($s['color'] ?? ''),
            ];
        }, array_filter($statuses, fn ($s) => is_array($s) && isset($s['code'], $s['label']))));
    }
}
