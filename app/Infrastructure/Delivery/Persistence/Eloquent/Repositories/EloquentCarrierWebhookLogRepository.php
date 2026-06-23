<?php

namespace App\Infrastructure\Delivery\Persistence\Eloquent\Repositories;

use App\Domain\Delivery\DeliveryCompany\Entities\CarrierWebhookLog;
use App\Domain\Delivery\DeliveryCompany\Repositories\CarrierWebhookLogRepositoryInterface;
use App\Infrastructure\Delivery\Persistence\Eloquent\Mappers\DeliveryMapper;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\CarrierWebhookLogModel;

final class EloquentCarrierWebhookLogRepository implements CarrierWebhookLogRepositoryInterface
{
    public function __construct(
        private readonly DeliveryMapper $mapper,
    ) {}

    public function create(CarrierWebhookLog $log): CarrierWebhookLog
    {
        $model = CarrierWebhookLogModel::create([
            'delivery_company_id' => $log->deliveryCompanyId,
            'event' => $log->event,
            'payload' => $log->payload,
            'processed' => $log->processed,
            'error' => $log->error,
            'created_at' => $log->createdAt ?? now(),
        ]);

        return $this->mapper->toCarrierWebhookLogEntity($model);
    }

    public function markProcessed(int $id, ?string $error = null): void
    {
        CarrierWebhookLogModel::where('id', $id)->update([
            'processed' => $error === null,
            'error' => $error,
        ]);
    }
}
