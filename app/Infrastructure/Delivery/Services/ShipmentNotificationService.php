<?php

namespace App\Infrastructure\Delivery\Services;

use App\Domain\Delivery\Shipment\Entities\Shipment;
use Illuminate\Support\Facades\Log;

final class ShipmentNotificationService
{
    public function notifyStatusChange(Shipment $shipment): void
    {
        Log::info('Shipment status notification triggered', [
            'shipment_id' => $shipment->id,
            'order_id' => $shipment->orderId,
            'status' => $shipment->status->value,
        ]);
    }
}
