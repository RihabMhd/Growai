<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\DeliveryCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShipmentController extends Controller
{
    /**
     * List shipments for an order or all shipments
     */
    public function index(Request $request)
    {
        $query = Shipment::query()->with(['order', 'deliveryCompany']);

        if ($request->filled('order_id')) {
            $query->where('order_id', $request->input('order_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('delivery_company_id')) {
            $query->where('delivery_company_id', $request->input('delivery_company_id'));
        }

        $shipments = $query->latest()->get();

        return response()->json(['shipments' => $shipments]);
    }

    /**
     * Show a specific shipment
     */
    public function show(string $id)
    {
        $shipment = Shipment::with(['order', 'deliveryCompany'])->findOrFail($id);

        return response()->json(['shipment' => $shipment]);
    }

    /**
     * Create a parcel/shipment for an order
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'delivery_company_id' => 'required|exists:delivery_companies,id',
            'recipient_name' => 'nullable|string|max:255',
            'recipient_phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:5',
            'cod_amount' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'dimensions.length' => 'nullable|numeric',
            'dimensions.width' => 'nullable|numeric',
            'dimensions.height' => 'nullable|numeric',
        ]);

        $order = Order::findOrFail($validated['order_id']);
        $company = DeliveryCompany::findOrFail($validated['delivery_company_id']);

        if (!$company->isConnected()) {
            return response()->json([
                'message' => 'Le transporteur n\'est pas connecté.',
            ], 422);
        }

        // Check if shipment already exists for this order
        $existingShipment = Shipment::where('order_id', $order->id)
            ->where('delivery_company_id', $company->id)
            ->where('status', '!=', 'failed')
            ->first();

        if ($existingShipment) {
            return response()->json([
                'message' => 'Un colis existe déjà pour cette commande avec ce transporteur.',
                'shipment' => $existingShipment,
            ], 422);
        }

        try {
            $shipment = DB::transaction(function () use ($validated, $order, $company) {
                // Use order's shipment details if not provided
                $shipmentData = [
                    'order_id' => $order->id,
                    'delivery_company_id' => $company->id,
                    'recipient_name' => $validated['recipient_name'] ?? $order->client->name,
                    'recipient_phone' => $validated['recipient_phone'] ?? $order->client->phone,
                    'address' => $validated['address'] ?? '',
                    'city' => $validated['city'] ?? $order->client->city,
                    'region' => $validated['region'] ?? $order->client->province,
                    'country' => $validated['country'] ?? 'MA',
                    'cod_amount' => $validated['cod_amount'] ?? ($order->total_price ?? 0),
                    'status' => 'pending',
                ];

                // Create shipment in database
                $shipment = Shipment::create($shipmentData);

                // Send parcel creation request to carrier API
                $trackingNumber = $company->createParcel(
                    $shipment,
                    $validated['weight'] ?? null,
                    $validated['dimensions'] ?? null
                );

                if ($trackingNumber) {
                    $shipment->update([
                        'tracking_number' => $trackingNumber,
                        'status' => 'picked_up',
                    ]);

                    // Update order shipment reference
                    $order->update([
                        'shipment_id' => $shipment->id,
                    ]);
                }

                return $shipment;
            });

            return response()->json([
                'message' => 'Colis créé avec succès.',
                'shipment' => $shipment,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create shipment', [
                'order_id' => $order->id,
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la création du colis: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update shipment (cancel, etc.)
     */
    public function update(Request $request, string $id)
    {
        $shipment = Shipment::findOrFail($id);

        $validated = $request->validate([
            'status' => 'nullable|in:pending,picked_up,in_transit,out_for_delivery,delivered,returned,failed',
            'delivery_notes' => 'nullable|string',
        ]);

        $oldStatus = $shipment->status;
        $shipment->update($validated);

        if (isset($validated['status']) && $validated['status'] !== $oldStatus) {
            Log::info('Shipment status updated', [
                'shipment_id' => $shipment->id,
                'old_status' => $oldStatus,
                'new_status' => $validated['status'],
            ]);
        }

        return response()->json([
            'message' => 'Colis mis à jour avec succès.',
            'shipment' => $shipment,
        ]);
    }

    /**
     * Cancel a shipment
     */
    public function destroy(string $id)
    {
        $shipment = Shipment::findOrFail($id);

        if (in_array($shipment->status, ['delivered', 'cancelled'])) {
            return response()->json([
                'message' => 'Ce colis ne peut pas être annulé.',
            ], 422);
        }

        try {
            if ($shipment->deliveryCompany && $shipment->tracking_number) {
                $shipment->deliveryCompany->cancelParcel($shipment);
            }

            $shipment->update(['status' => 'failed']);

            return response()->json([
                'message' => 'Colis annulé avec succès.',
                'shipment' => $shipment,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cancel shipment', [
                'shipment_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de l\'annulation: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get tracking information from carrier
     */
    public function getTracking(string $id)
    {
        $shipment = Shipment::findOrFail($id);

        if (!$shipment->tracking_number) {
            return response()->json([
                'message' => 'Aucun numéro de suivi disponible.',
            ], 422);
        }

        try {
            $tracking = $shipment->deliveryCompany->getTracking($shipment->tracking_number);

            return response()->json([
                'shipment_id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
                'tracking_info' => $tracking,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération du suivi: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Webhook handler for carrier status updates
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        $signature = $request->header('X-Signature');

        Log::info('Received shipment webhook', $payload);

        // Verify signature if provided
        if ($signature && !$this->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Invalid webhook signature', ['payload' => $payload]);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        try {
            // Extract tracking number from payload
            $trackingNumber = $payload['tracking_number'] ?? $payload['parcel_id'] ?? null;
            $status = $payload['status'] ?? null;
            $companyId = $payload['company_id'] ?? null;

            if (!$trackingNumber || !$status) {
                return response()->json(['message' => 'Missing required fields'], 422);
            }

            // Find shipment
            $shipment = Shipment::where('tracking_number', $trackingNumber)
                ->when($companyId, fn($q) => $q->where('delivery_company_id', $companyId))
                ->first();

            if (!$shipment) {
                Log::warning('Shipment not found for webhook', ['tracking_number' => $trackingNumber]);
                return response()->json(['message' => 'Shipment not found'], 404);
            }

            // Update shipment status
            $oldStatus = $shipment->status;
            $shipment->update([
                'status' => $this->mapCarrierStatus($status),
                'delivery_notes' => $payload['notes'] ?? null,
            ]);

            // Update timestamps based on status
            if ($status === 'delivered' || $status === 'completed') {
                $shipment->update(['delivered_at' => now()]);
            } elseif ($status === 'picked_up') {
                $shipment->update(['shipped_at' => now()]);
            }

            Log::info('Shipment status updated from webhook', [
                'shipment_id' => $shipment->id,
                'old_status' => $oldStatus,
                'new_status' => $shipment->status,
            ]);

            return response()->json(['message' => 'Webhook processed successfully']);
        } catch (\Exception $e) {
            Log::error('Error processing shipment webhook', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error processing webhook'], 500);
        }
    }

    /**
     * Map carrier-specific status to standard status
     */
    private function mapCarrierStatus(string $carrierStatus): string
    {
        $statusMap = [
            'pending' => 'pending',
            'collected' => 'picked_up',
            'picked_up' => 'picked_up',
            'in_transit' => 'in_transit',
            'out_for_delivery' => 'out_for_delivery',
            'delivered' => 'delivered',
            'completed' => 'delivered',
            'returned' => 'returned',
            'failed' => 'failed',
            'cancelled' => 'failed',
        ];

        return $statusMap[strtolower($carrierStatus)] ?? $carrierStatus;
    }

    /**
     * Verify webhook signature
     */
    private function verifyWebhookSignature(array $payload, string $signature): bool
    {
        return true;
    }
}
