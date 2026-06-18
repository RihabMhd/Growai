<?php

namespace App\Application\Delivery\Shipment\Actions;

use App\Application\Delivery\Shipment\Commands\CreateShipmentCommand;
use App\Domain\Delivery\DeliveryCompany\Exceptions\CarrierNotConnectedException;
use App\Domain\Delivery\DeliveryCompany\Repositories\CarrierConfigurationRepositoryInterface;
use App\Domain\Delivery\DeliveryCompany\Repositories\DeliveryCompanyRepositoryInterface;
use App\Domain\Delivery\Shipment\Entities\Shipment;
use App\Domain\Delivery\Shipment\Exceptions\ShipmentAlreadyExistsException;
use App\Domain\Delivery\Shipment\Repositories\ShipmentRepositoryInterface;
use App\Domain\Delivery\Shipment\Services\ShipmentLifecycleService;
use App\Domain\Delivery\Shipment\ValueObjects\Address;
use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;
use App\Domain\Orders\Models\Order;
use App\Infrastructure\Delivery\Queue\Jobs\CreateParcelJob;
use Illuminate\Support\Facades\DB;

final class CreateShipmentAction
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $shipments,
        private readonly DeliveryCompanyRepositoryInterface $companies,
        private readonly CarrierConfigurationRepositoryInterface $configurations,
        private readonly ShipmentLifecycleService $lifecycle,
    ) {}

    public function execute(CreateShipmentCommand $command): Shipment
    {
        $data = $command->data;
        $company = $this->companies->findById($data->deliveryCompanyId);

        if (! $company || ! $company->isConnected()) {
            throw CarrierNotConnectedException::forCompany($data->deliveryCompanyId);
        }

        if ($this->shipments->findActiveForOrderAndCarrier($data->orderId, $data->deliveryCompanyId)) {
            throw ShipmentAlreadyExistsException::forOrderAndCarrier($data->orderId, $data->deliveryCompanyId);
        }

        $order = Order::with('client')->findOrFail($data->orderId);

        return DB::transaction(function () use ($data, $order) {
            $shipment = $this->shipments->save(new Shipment(
                id: null,
                orderId: $order->id,
                deliveryCompanyId: $data->deliveryCompanyId,
                trackingNumber: null,
                status: ShipmentStatusSlug::labelCreated(),
                address: new Address(
                    recipientName: $data->recipientName ?? $order->client?->name ?? 'N/A',
                    recipientPhone: $data->recipientPhone ?? $order->client?->phone ?? 'N/A',
                    street: $data->address ?? $order->street ?? '',
                    city: $data->city ?? $order->city,
                    region: $data->region ?? $order->province,
                    country: $data->country ?? 'MA',
                ),
                codAmount: $data->codAmount ?? (float) ($order->total_price ?? 0),
            ));

            $this->lifecycle->recordStatusChange(
                shipment: $shipment,
                newStatus: $shipment->status,
                source: 'system',
                description: 'Shipment created, awaiting carrier parcel creation.',
            );

            CreateParcelJob::dispatch(
                shipmentId: $shipment->id,
                weight: $data->weight,
                dimensions: $data->dimensions,
            );

            return $shipment;
        });
    }
}
