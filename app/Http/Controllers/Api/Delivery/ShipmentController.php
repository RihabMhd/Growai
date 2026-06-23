<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Application\Delivery\Shipment\Actions\CancelShipmentAction;
use App\Application\Delivery\Shipment\Actions\CreateShipmentAction;
use App\Application\Delivery\Shipment\Actions\GetShipmentAction;
use App\Application\Delivery\Shipment\Actions\GetShipmentTrackingAction;
use App\Application\Delivery\Shipment\Actions\HandleCarrierWebhookAction;
use App\Application\Delivery\Shipment\Actions\ListShipmentsAction;
use App\Application\Delivery\Shipment\Actions\UpdateShipmentAction;
use App\Application\Delivery\Shipment\Commands\CancelShipmentCommand;
use App\Application\Delivery\Shipment\Commands\CreateShipmentCommand;
use App\Application\Delivery\Shipment\Commands\HandleCarrierWebhookCommand;
use App\Application\Delivery\Shipment\Commands\UpdateShipmentCommand;
use App\Application\Delivery\Shipment\DTOs\CreateShipmentDTO;
use App\Application\Delivery\Shipment\Queries\GetShipmentQuery;
use App\Application\Delivery\Shipment\Queries\GetShipmentTrackingQuery;
use App\Application\Delivery\Shipment\Queries\ListShipmentsQuery;
use App\Domain\Delivery\DeliveryCompany\Exceptions\CarrierNotConnectedException;
use App\Domain\Delivery\Shipment\Exceptions\ShipmentAlreadyExistsException;
use App\Domain\Delivery\Shipment\Exceptions\ShipmentCannotBeCancelledException;
use App\Domain\Delivery\Shipment\Exceptions\ShipmentNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShipmentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ShipmentController extends Controller
{
    public function __construct(
        private readonly ListShipmentsAction $listShipments,
        private readonly GetShipmentAction $getShipment,
        private readonly CreateShipmentAction $createShipment,
        private readonly UpdateShipmentAction $updateShipment,
        private readonly CancelShipmentAction $cancelShipment,
        private readonly GetShipmentTrackingAction $getTracking,
        private readonly HandleCarrierWebhookAction $handleWebhook,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $shipments = $this->listShipments->execute(ListShipmentsQuery::fromRequest($request));

        return response()->json(['shipments' => $shipments]);
    }

    public function show(string $id): JsonResponse
    {
        try {
            return response()->json([
                'shipment' => $this->getShipment->execute(new GetShipmentQuery((int) $id)),
            ]);
        } catch (ShipmentNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function store(StoreShipmentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $shipment = $this->createShipment->execute(new CreateShipmentCommand(
                new CreateShipmentDTO(
                    orderId: (int) $validated['order_id'],
                    deliveryCompanyId: (int) $validated['delivery_company_id'],
                    recipientName: $validated['recipient_name'] ?? null,
                    recipientPhone: $validated['recipient_phone'] ?? null,
                    address: $validated['address'] ?? null,
                    city: $validated['city'] ?? null,
                    region: $validated['region'] ?? null,
                    country: $validated['country'] ?? null,
                    codAmount: isset($validated['cod_amount']) ? (float) $validated['cod_amount'] : null,
                    weight: isset($validated['weight']) ? (float) $validated['weight'] : null,
                    dimensions: $validated['dimensions'] ?? null,
                )
            ));

            return response()->json([
                'message' => 'Colis créé avec succès. Création chez le transporteur en cours.',
                'shipment' => $shipment,
            ], 201);
        } catch (CarrierNotConnectedException|ShipmentAlreadyExistsException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Erreur lors de la création du colis: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|in:label_created,ready_for_pickup,picked_up,out_for_delivery,delivered,delayed,failure,returned',
            'delivery_notes' => 'nullable|string',
        ]);

        try {
            $shipment = $this->updateShipment->execute(new UpdateShipmentCommand(
                shipmentId: (int) $id,
                statusSlug: $validated['status'] ?? null,
                deliveryNotes: $validated['delivery_notes'] ?? null,
            ));

            return response()->json([
                'message' => 'Colis mis à jour avec succès.',
                'shipment' => $shipment,
            ]);
        } catch (ShipmentNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $shipment = $this->cancelShipment->execute(new CancelShipmentCommand((int) $id));

            return response()->json([
                'message' => 'Colis annulé avec succès.',
                'shipment' => $shipment,
            ]);
        } catch (ShipmentNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (ShipmentCannotBeCancelledException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'annulation: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function getTracking(string $id): JsonResponse
    {
        try {
            $tracking = $this->getTracking->execute(new GetShipmentTrackingQuery((int) $id));

            return response()->json($tracking);
        } catch (ShipmentNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function handleWebhook(Request $request, string $companyId): JsonResponse
    {
        $logId = $this->handleWebhook->execute(new HandleCarrierWebhookCommand(
            deliveryCompanyId: (int) $companyId,
            payload: $request->all(),
            signature: $request->header('X-Signature'),
        ));

        return response()->json([
            'message' => 'Webhook received and queued for processing.',
            'webhook_log_id' => $logId,
        ]);
    }
}
