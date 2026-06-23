<?php

namespace App\Application\Delivery\Shipment\Actions;

use App\Application\Delivery\Shipment\Commands\HandleCarrierWebhookCommand;
use App\Domain\Delivery\DeliveryCompany\Entities\CarrierWebhookLog;
use App\Domain\Delivery\DeliveryCompany\Repositories\CarrierWebhookLogRepositoryInterface;
use App\Infrastructure\Delivery\Queue\Jobs\ProcessCarrierWebhookJob;

final class HandleCarrierWebhookAction
{
    public function __construct(
        private readonly CarrierWebhookLogRepositoryInterface $webhookLogs,
    ) {}

    public function execute(HandleCarrierWebhookCommand $command): int
    {
        $log = $this->webhookLogs->create(new CarrierWebhookLog(
            id: null,
            deliveryCompanyId: $command->deliveryCompanyId,
            event: $command->payload['event'] ?? $command->payload['type'] ?? 'tracking_update',
            payload: $command->payload,
            processed: false,
            createdAt: new \DateTimeImmutable,
        ));

        ProcessCarrierWebhookJob::dispatch(
            webhookLogId: $log->id,
            deliveryCompanyId: $command->deliveryCompanyId,
            signature: $command->signature,
        );

        return $log->id;
    }
}
