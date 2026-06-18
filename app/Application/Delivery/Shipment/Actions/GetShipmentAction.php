<?php

namespace App\Application\Delivery\Shipment\Actions;

use App\Application\Delivery\Shipment\Queries\GetShipmentQuery;
use App\Domain\Delivery\Shipment\Exceptions\ShipmentNotFoundException;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\ShipmentModel;

final class GetShipmentAction
{
    public function execute(GetShipmentQuery $query): ShipmentModel
    {
        $shipment = ShipmentModel::with(['order', 'deliveryCompany', 'status', 'histories'])
            ->find($query->shipmentId);

        if (! $shipment) {
            throw ShipmentNotFoundException::withId($query->shipmentId);
        }

        return $shipment;
    }
}
