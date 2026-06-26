<?php

namespace App\Application\Delivery\Shipment\Actions;

use App\Application\Delivery\Shipment\Commands\CreateOrderShipmentCommand;
use App\Application\Delivery\Shipment\DTOs\CreateOrderShipmentDTO;
use App\Domain\Delivery\DeliveryCompany\Exceptions\CarrierNotConnectedException;
use App\Domain\Delivery\Shipment\Entities\Shipment;
use App\Domain\Delivery\Shipment\Exceptions\OrderShipmentAlreadyExistsException;
use App\Domain\Delivery\Shipment\Repositories\ShipmentRepositoryInterface;
use App\Domain\Delivery\Shipment\Services\ShipmentLifecycleService;
use App\Domain\Delivery\Shipment\ValueObjects\Address;
use App\Domain\Delivery\Shipment\ValueObjects\ShipmentStatusSlug;
use App\Domain\Delivery\DeliveryCompany\Repositories\DeliveryCompanyRepositoryInterface;
use App\Infrastructure\Delivery\Queue\Jobs\CreateParcelJob;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Services\OrderAuditLogger;
use Illuminate\Support\Facades\DB;

final class CreateOrderShipmentAction
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $shipments,
        private readonly DeliveryCompanyRepositoryInterface $companies,
        private readonly ShipmentLifecycleService $lifecycle,
        private readonly OrderAuditLogger $orderAuditLogger,
    ) {}

    public function execute(CreateOrderShipmentCommand $command): Shipment
    {
        $data = $command->data;

        $company = $this->companies->findById($data->deliveryCompanyId);

        if (! $company || ! $company->isActive) {
            throw new \RuntimeException('Delivery company is not active.');
        }

        if (! $company->isConnected()) {
            throw CarrierNotConnectedException::forCompany($data->deliveryCompanyId);
        }

        $order = Order::with('client')->findOrFail($data->orderId);

        if (! empty($order->shipment_id)) {
            throw OrderShipmentAlreadyExistsException::forOrder($order->id);
        }

        $metadata = [
            'delivery_company_id' => $data->deliveryCompanyId,
            'carrier_slug'         => $company->slug,
            'driver'                => strcasecmp($company->slug, 'ameex') === 0 ? 'ameex' : 'generic',
        ];

        $isAmeex = strcasecmp((string) $company->slug, 'ameex') === 0;
        if ($isAmeex) {
            $metadata['ameex'] = [
                'api_id' => $data->apiId,
                'delivery_type' => $data->deliveryType,
                'openable' => $data->openable,
                'test_product' => $data->testProduct,
                'fragile' => $data->fragile,
                'product' => $data->product,
                'exchange' => $data->exchange,
            ];
        }

        return DB::transaction(function () use ($order, $data, $metadata, $company, $isAmeex) {
            // IMPORTANT: update orders.shipment_id immediately to enforce uniqueness at business level
            // (job will also update it later; update again is safe)
            $shipment = $this->shipments->save(
                new Shipment(
                    id: null,
                    orderId: $order->id,
                    deliveryCompanyId: $data->deliveryCompanyId,
                    trackingNumber: null,
                    status: ShipmentStatusSlug::labelCreated(),
                    address: new Address(
                        recipientName: $data->clientName,
                        recipientPhone: $data->phone,
                        street: $data->address,
                        city: $data->city,
                        region: null,
                        country: 'MA',
                    ),
                    codAmount: $data->total,
                    deliveryNotes: $data->note,
                )
            );

            // Persist metadata via payload field if present in DB layer later
            // (migration + mapper updates will finalize this)

            $this->lifecycle->recordStatusChange(
                shipment: $shipment,
                newStatus: $shipment->status,
                source: 'system',
                description: 'Shipment created, awaiting carrier parcel creation.',
            );

            CreateParcelJob::dispatch(
                shipmentId: $shipment->id,
                weight: null,
                dimensions: null,
            );

            Order::where('id', $order->id)->update(['shipment_id' => $shipment->id]);

            $this->orderAuditLogger->log(
                order: $order,
                userId: auth()->id() ?? $order->assigned_to,
                actionType: 'shipment_created',
                oldValue: null,
                newValue: null,
                description: 'Parcel created via ' . $company->name,
            );

            return $shipment;
        });
    }
}

