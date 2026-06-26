<?php

namespace App\Http\Controllers\Api\Order;

use App\Application\Delivery\Shipment\Actions\CreateOrderShipmentAction;
use App\Application\Delivery\Shipment\Commands\CreateOrderShipmentCommand;
use App\Application\Delivery\Shipment\DTOs\CreateOrderShipmentDTO;
use App\Domain\Delivery\DeliveryCompany\Exceptions\CarrierNotConnectedException;
use App\Domain\Delivery\Shipment\Exceptions\OrderShipmentAlreadyExistsException;
use App\Http\Requests\StoreOrderShipmentRequest;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminOrderShipmentController extends Controller
{

    public function __construct(
        private readonly CreateOrderShipmentAction $createShipment,
    ) {}

    public function store(Request $request, int|string $orderId): JsonResponse
    {
        // Use dedicated request validation
        $rules = (new StoreOrderShipmentRequest())->rules();
        $messages = (new StoreOrderShipmentRequest())->messages();
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules, $messages);
        $validated = $validator->validate();

        try {
            $shipment = $this->createShipment->execute(new CreateOrderShipmentCommand(
                data: new CreateOrderShipmentDTO(
                    orderId: (int) $orderId,
                    deliveryCompanyId: (int) $validated['delivery_company_id'],
                    city: $validated['city'],
                    clientName: $validated['client_name'],
                    phone: $validated['phone'],
                    address: $validated['address'],
                    total: (float) $validated['total'],
                    note: $validated['note'] ?? null,
                    apiId: $validated['api_id'] ?? null,
                    deliveryType: $validated['delivery_type'] ?? null,
                    openable: array_key_exists('openable', $validated) ? (bool) $validated['openable'] : null,
                    testProduct: array_key_exists('test_product', $validated) ? (bool) $validated['test_product'] : null,
                    fragile: array_key_exists('fragile', $validated) ? (bool) $validated['fragile'] : null,
                    product: $validated['product'] ?? null,
                    exchange: array_key_exists('exchange', $validated) ? (bool) $validated['exchange'] : null,
                )
            ));

            return response()->json([
                'success' => true,
                'message' => 'Shipment created successfully.',
                'shipment' => $shipment,
            ], 201);
        } catch (OrderShipmentAlreadyExistsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'A shipment already exists for this order.',
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

