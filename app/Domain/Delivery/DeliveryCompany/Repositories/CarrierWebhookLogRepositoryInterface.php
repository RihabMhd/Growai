<?php

namespace App\Domain\Delivery\DeliveryCompany\Repositories;

use App\Domain\Delivery\DeliveryCompany\Entities\CarrierWebhookLog;

interface CarrierWebhookLogRepositoryInterface
{
    public function create(CarrierWebhookLog $log): CarrierWebhookLog;

    public function markProcessed(int $id, ?string $error = null): void;
}
