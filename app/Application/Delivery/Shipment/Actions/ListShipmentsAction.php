<?php

namespace App\Application\Delivery\Shipment\Actions;

use App\Application\Delivery\Shipment\Queries\ListShipmentsQuery;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\ShipmentModel;

final class ListShipmentsAction
{
    public function execute(ListShipmentsQuery $query): array
    {
        $builder = ShipmentModel::with(['order', 'deliveryCompany', 'status'])->latest();

        if ($query->orderId) {
            $builder->where('order_id', $query->orderId);
        }

        if ($query->statusSlug) {
            $builder->whereHas('status', fn ($q) => $q->where('slug', $query->statusSlug));
        }

        if ($query->deliveryCompanyId) {
            $builder->where('delivery_company_id', $query->deliveryCompanyId);
        }

        return $builder->get()->all();
    }
}
