<?php

namespace App\Infrastructure\Delivery\Services;

use App\Domain\Delivery\Shipment\Entities\Shipment;
use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\ShipmentStatusModel;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\ShipmentHistoryModel;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\ShipmentModel;
use Illuminate\Support\Facades\Log;

final class OrderShipmentSyncService
{
    private const ORDER_STATUS_MAP = [
        ShipmentStatusSlug::UNFULFILLED => 'unfulfilled',
        ShipmentStatusSlug::LABEL_CREATED => 'label_created',
        ShipmentStatusSlug::LABEL_PURCHASED => 'label_purchased',
        ShipmentStatusSlug::LABEL_PRINTED => 'label_printed',
        ShipmentStatusSlug::CONFIRMED => 'confirmed',
        ShipmentStatusSlug::IN_TRANSIT => 'in_transit',
        ShipmentStatusSlug::OUT_FOR_DELIVERY => 'out_for_delivery',
        ShipmentStatusSlug::DELIVERED => 'delivered',
        ShipmentStatusSlug::ATTEMPTED_DELIVERY => 'attempted_delivery',
        ShipmentStatusSlug::DELIVERY_FAILED => 'delivery_failed',
        ShipmentStatusSlug::DELAYED => 'delayed',
        ShipmentStatusSlug::RETURNED => 'returned',
        ShipmentStatusSlug::PARTIAL => 'partial',
        ShipmentStatusSlug::FULFILLED => 'fulfilled',
    ];

    public function syncFromShipment(Shipment $shipment): void
    {
        $orderStatus = self::ORDER_STATUS_MAP[$shipment->status->value] ?? null;

        if (! $orderStatus) {
            return;
        }

        $order = \App\Domain\Orders\Models\Order::find($shipment->orderId);

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

    public function getShipmentStatusHistory(int $shipmentId): array
    {
        return ShipmentHistoryModel::where('shipment_id', $shipmentId)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($history) {
                $fulfillmentStatus = ShipmentStatusModel::where('slug', $history->new_status)->first();

                return [
                    'id' => $history->id,
                    'fulfillment_status' => $history->new_status,
                    'fulfillment_status_label' => $fulfillmentStatus?->name ?? $history->new_status,
                    'fulfillment_status_color' => $fulfillmentStatus?->color ?? '#6B7280',
                    'provider_status' => $history->provider_status,
                    'source' => $history->source,
                    'description' => $history->description,
                    'created_at' => $history->created_at,
                ];
            })
            ->all();
    }
}
