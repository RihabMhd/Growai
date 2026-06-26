<?php

namespace App\Http\Controllers\Api\AdminOrder;

use App\Application\Delivery\Shipment\Actions\CreateOrderShipmentAction;
use App\Application\Delivery\Shipment\Commands\CreateOrderShipmentCommand;
use App\Domain\Delivery\DeliveryCompany\Exceptions\CarrierNotConnectedException;
use App\Domain\Delivery\Shipment\Exceptions\OrderShipmentAlreadyExistsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderShipmentRequest;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\DeliveryCompanyModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminOrderShipmentController extends Controller
{
    public function __construct(
        private readonly CreateOrderShipmentAction $createShipment,
    ) {}

    public function store(Request $request, int|string $orderId): JsonResponse
    {
        /** @var StoreOrderShipmentRequest $validatedRequest */
        $validatedRequest = $request->validate([]); // placeholder; actual validation via StoreOrderShipmentRequest below
        $validated = $request->validate([]);

        // We can't type-hint FormRequest here via this controller signature safely without routing adjustments.
        // So we manually invoke StoreOrderShipmentRequest via container.
        $formRequest = app(StoreOrderShipmentRequest::class);
        $formRequest->merge($request->all());
        $formRequest->setRouteResolver(fn () => $request->route());
        $formRequest->setContainer($request->getContainer());

        $validated = $formRequest->validated();

        try {
            $shipment = $this->createShipment->execute(new CreateOrderShipmentCommand(
                data: app(\App\Application\Delivery\Shipment\DTOs\CreateOrderShipmentDTO::class, [
                    'orderId'           => (int) $orderId,
                    'deliveryCompanyId'=> (int) $validated['delivery_company_id'],
                    'city'              => $validated['city'],
                    'clientName'       => $validated['client_name'],
                    'phone'             => $validated['phone'],
                    'address'           => $validated['address'],
                    'total'             => (float) $validated['total'],
                    'note'              => $validated['note'] ?? null,
                    'apiId'            => $validated['api_id'] ?? null,
                    'deliveryType'    => $validated['delivery_type'] ?? null,
                    'openable'        => array_key_exists('openable', $validated) ? (bool) $validated['openable'] : null,
                    'testProduct'     => array_key_exists('test_product', $validated) ? (bool) $validated['test_product'] : null,
                    'fragile'         => array_key_exists('fragile', $validated) ? (bool) $validated['fragile'] : null,
                    'product'         => $validated['product'] ?? null,
                    'exchange'        => array_key_exists('exchange', $validated) ? (bool) $validated['exchange'] : null,
                ])
            ));

            return response()->json([
                'success' => true,
                'message' => 'Shipment created successfully.',
                'shipment' => $shipment,
            ], 201);
        } catch (OrderShipmentAlreadyExistsException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (CarrierNotConnectedException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating shipment: ' . $e->getMessage(),
            ], 422);
        }
    }
}

