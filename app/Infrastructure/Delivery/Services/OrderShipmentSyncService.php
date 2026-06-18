<?php

namespace App\Infrastructure\Delivery\Services;

use App\Domain\Delivery\Shipment\Entities\Shipment;
use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;
use App\Domain\Orders\Models\Order;
use Illuminate\Support\Facades\Log;

final class OrderShipmentSyncService
{
    private const ORDER_STATUS_MAP = [
        ShipmentStatusSlug::LABEL_CREATED => 'label_created',
        ShipmentStatusSlug::READY_FOR_PICKUP => 'ready_for_pickup',
        ShipmentStatusSlug::PICKED_UP => 'picked_up',
        ShipmentStatusSlug::OUT_FOR_DELIVERY => 'out_for_delivery',
        ShipmentStatusSlug::DELIVERED => 'delivered',
        ShipmentStatusSlug::DELAYED => 'delayed',
        ShipmentStatusSlug::FAILURE => 'attempted_delivery',
        ShipmentStatusSlug::RETURNED => 'returned',
    ];

    public function syncFromShipment(Shipment $shipment): void
    {
        $orderStatus = self::ORDER_STATUS_MAP[$shipment->status->value] ?? null;

        if (! $orderStatus) {
            return;
        }

        $order = Order::find($shipment->orderId);

        if (! $order) {
            return;
        }

        $order->update([
            'status' => $orderStatus,
            'shipment_id' => $shipment->id,
        ]);

        Log::info('Order synced from shipment status', [
            'order_id' => $order->id,
            'shipment_id' => $shipment->id,
            'order_status' => $orderStatus,
        ]);
    }
}
