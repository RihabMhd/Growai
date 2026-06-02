<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderSource;
use App\Models\OrderHistory;
use App\Models\Product;
use App\Models\Client;
use App\Models\Shop;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Display a listing of orders filtered by agent visibility.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Order::query()
            ->with(['items.product', 'client', 'shop', 'assignedAgent', 'shipments'])
            ->orderBy('created_at', 'desc');

        // 1. Role-based visibility
        if ($user->role === 'staff') {
            $assignedProductIds = $user->products()->pluck('products.id')->toArray();
            if (count($assignedProductIds) > 0) {
                $query->whereHas('items', function ($q) use ($assignedProductIds) {
                    $q->whereIn('product_id', $assignedProductIds);
                });
            }
        }

        // 2. Search by order number
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('order_number', 'like', "%{$search}%");
        }

        // 3. Abandoned filter - only apply if type is specified
        if ($request->filled('type')) {
            $isAbandoned = $request->input('type') === 'abandoned';
            $query->where('is_abandoned', $isAbandoned);
        }

        // 4. Status filter
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // 5. Get all matching results (frontend expects a flat array instead of pagination object)
        $orders = $query->get();

        // 6. Calculate metrics from the filtered query (not the paginated collection)
        $metricsQuery = Order::query();
        
        // Apply same filters for metrics
        if ($user->role === 'staff') {
            $assignedProductIds = $user->products()->pluck('products.id')->toArray();
            if (count($assignedProductIds) > 0) {
                $metricsQuery->whereHas('items', function ($q) use ($assignedProductIds) {
                    $q->whereIn('product_id', $assignedProductIds);
                });
            }
        }
        if ($request->filled('search')) {
            $metricsQuery->where('order_number', 'like', "%{$request->input('search')}%");
        }
        if ($request->filled('type')) {
            $metricsQuery->where('is_abandoned', $request->input('type') === 'abandoned');
        }

        $totalOrders    = $metricsQuery->count();
        $confirmedCount = (clone $metricsQuery)->whereIn('status', ['confirmed', 'delivered', 'processing', 'shipped'])->count();
        $cancelledCount = (clone $metricsQuery)->whereIn('status', ['cancelled', 'returned'])->count();
        $pendingCount   = (clone $metricsQuery)->where('status', 'pending')->count();
        $confirmationRate = $totalOrders > 0 ? round(($confirmedCount / $totalOrders) * 100) : 0;

        return response()->json([
            'orders'  => $orders,
            'metrics' => [
                'total_orders'      => $totalOrders,
                'confirmed'         => $confirmedCount,
                'cancelled'         => $cancelledCount,
                'pending'           => $pendingCount,
                'confirmation_rate' => $confirmationRate . '%',
            ],
            'active_agents' => $user->role === 'admin'
                ? User::where('role', 'staff')->where('is_active', true)->get()
                : [],
        ]);
    }

    /**
     * Store a manually created order.
     *
     * Uses the existing schema correctly:
     *  - Customer data   → clients table
     *  - Shipping address → shipments table
     *  - Source/channel  → order_sources table + orders.source_channel (denormalized)
     *  - Products        → order_items table
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Customer
            'customer_name'    => 'required|string|max:255',
            'customer_phone'   => 'required|string|max:30',
            'customer_email'   => 'nullable|email|max:255',

            // Address (goes to shipments + clients)
            'province'         => 'nullable|string|max:100',
            'city'             => 'nullable|string|max:100',
            'street'           => 'nullable|string|max:255',

            // Source channel
            'source'           => 'nullable|string|max:30',

            // Order meta
            'notes'            => 'nullable|string',
            'is_abandoned'     => 'nullable|boolean',
            'shipping_price'   => 'nullable|numeric|min:0',

            // Items
            'items'            => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        // Find or create the client record (match by phone — the natural key)
        $client = Client::firstOrCreate(
            ['phone' => $validated['customer_phone']],
            [
                'name'     => $validated['customer_name'],
                'email'    => $validated['customer_email'] ?? null,
                'city'     => $validated['city'] ?? null,
                'province' => $validated['province'] ?? null,
                'address'  => $validated['street'] ?? null,
            ]
        );

        // If client already existed, update their name/email/address in case they changed
        $client->update([
            'name'     => $validated['customer_name'],
            'email'    => $validated['customer_email'] ?? $client->email,
            'city'     => $validated['city'] ?? $client->city,
            'province' => $validated['province'] ?? $client->province,
            'address'  => $validated['street'] ?? $client->address,
        ]);

        // Use the first available shop (or null for manual orders with no shop)
        $shop = Shop::first();

        $order = DB::transaction(function () use ($validated, $client, $shop) {

            $team = \App\Models\Team::first();
            $prefix = ($team && $team->order_prefix) ? $team->order_prefix : 'ORD';

            // 1. Create the order
            $order = Order::create([
                'shop_id'        => $shop?->id,
                'client_id'      => $client->id,
                'order_number'   => $prefix . '-' . strtoupper(Str::random(8)),
                'total_price'    => 0.00,
                'shipping_price' => (float) ($validated['shipping_price'] ?? 0),
                'discount'       => 0.00,
                'currency'       => 'MAD',
                'status'         => 'pending',
                'financial_status' => 'unpaid',
                'notes'          => $validated['notes'] ?? null,
                'source_channel' => $validated['source'] ?? 'manual',
                'is_abandoned'   => $validated['is_abandoned'] ?? false,
                'abandoned_at'   => ($validated['is_abandoned'] ?? false) ? now() : null,
            ]);

            // 2. Create order items and calculate total
            $subtotal = 0.00;
            foreach ($validated['items'] as $itemData) {
                $product  = Product::findOrFail($itemData['product_id']);
                $qty      = (int) $itemData['quantity'];

                // Price: use first variant price if available, else cost, else 0
                $unitPrice = 0;
                if (!empty($product->variants)) {
                    $variants  = is_array($product->variants) ? $product->variants : json_decode($product->variants, true);
                    $unitPrice = (float) ($variants[0]['price'] ?? $product->cost ?? 0);
                } else {
                    $unitPrice = (float) ($product->cost ?? 0);
                }

                $lineTotal = $unitPrice * $qty;
                $subtotal += $lineTotal;

                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $product->id,
                    'product_name' => $product->title,
                    'quantity'     => $qty,
                    'unit_price'   => $unitPrice,
                    'total_price'  => $lineTotal,
                ]);
            }

            // 3. Update order total (subtotal + shipping)
            $shippingPrice = (float) ($validated['shipping_price'] ?? 0);
            $order->update(['total_price' => $subtotal + $shippingPrice]);

            // 4. Create order source record (full tracking in order_sources table)
            if (!empty($validated['source'])) {
                OrderSource::create([
                    'order_id' => $order->id,
                    'type'     => $validated['source'],
                    'platform' => $validated['source'],
                ]);
            }

            // 5. Create a shipment record with the delivery address
            if (!empty($validated['city']) || !empty($validated['province']) || !empty($validated['street'])) {
                Shipment::create([
                    'order_id'         => $order->id,
                    'delivery_company_id' => null, // will be assigned later
                    'status'           => 'pending',
                    'recipient_name'   => $validated['customer_name'],
                    'recipient_phone'  => $validated['customer_phone'],
                    'address'          => implode(', ', array_filter([
                        $validated['street']   ?? null,
                        $validated['city']     ?? null,
                        $validated['province'] ?? null,
                    ])),
                    'city'             => $validated['city'] ?? null,
                    'region'           => $validated['province'] ?? null,
                    'country'          => 'MA',
                    'cod_amount'       => $subtotal + (float) ($validated['shipping_price'] ?? 0),
                ]);
            }

            // 6. Log creation in order history
            OrderHistory::create([
                'order_id'    => $order->id,
                'user_id'     => auth()->id(),
                'action_type' => 'status_changed',
                'old_value'   => null,
                'new_value'   => 'pending',
                'description' => 'Commande créée manuellement.',
            ]);

            return $order;
        });

        $order->load(['items.product', 'client', 'assignedAgent']);

        return response()->json([
            'message' => 'Commande créée avec succès !',
            'order'   => $order,
        ], 201);
    }

    /**
     * Display the specified order with full history.
     */
    public function show(string $id)
    {
        $order = Order::with(['items.product', 'client', 'shop', 'assignedAgent', 'histories.user', 'shipments'])
            ->findOrFail($id);

        return response()->json(['order' => $order]);
    }

    /**
     * Update order status or financial status.
     */
    public function update(Request $request, string $id)
    {
        $order = Order::with(['client', 'items', 'shipments'])->findOrFail($id);

        $validated = $request->validate([
            'status'           => 'nullable|string',
            'financial_status' => 'nullable|string|in:unpaid,pending,paid,refunded',
            'notes'            => 'nullable|string',
            
            // Client editing
            'customer_name'    => 'nullable|string|max:255',
            'customer_phone'   => 'nullable|string|max:30',
            'customer_email'   => 'nullable|email|max:255',

            // Address editing (shipment + client)
            'province'         => 'nullable|string|max:100',
            'city'             => 'nullable|string|max:100',
            'street'           => 'nullable|string|max:255',
            'shipping_price'   => 'nullable|numeric|min:0',

            // Items editing
            'items'            => 'nullable|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity'   => 'required_with:items|integer|min:1',
        ]);

        $oldStatus = $order->status;

        DB::transaction(function () use ($validated, $order) {
            // 1. Update status and notes on the order
            $orderData = [];
            if (isset($validated['status'])) $orderData['status'] = $validated['status'];
            if (isset($validated['financial_status'])) $orderData['financial_status'] = $validated['financial_status'];
            if (isset($validated['notes'])) $orderData['notes'] = $validated['notes'];
            if (isset($validated['shipping_price'])) $orderData['shipping_price'] = (float)$validated['shipping_price'];

            // 2. Update Client details
            if ($order->client) {
                $clientData = [];
                if (!empty($validated['customer_name'])) $clientData['name'] = $validated['customer_name'];
                if (!empty($validated['customer_phone'])) $clientData['phone'] = $validated['customer_phone'];
                if (isset($validated['customer_email'])) $clientData['email'] = $validated['customer_email'];
                if (isset($validated['city'])) $clientData['city'] = $validated['city'];
                if (isset($validated['province'])) $clientData['province'] = $validated['province'];
                if (isset($validated['street'])) $clientData['address'] = $validated['street'];

                if (!empty($clientData)) {
                    $order->client->update($clientData);
                }
            }

            // 3. Update Shipment details
            $shipment = $order->shipments()->first();
            if ($shipment) {
                $shipmentData = [];
                if (!empty($validated['customer_name'])) $shipmentData['recipient_name'] = $validated['customer_name'];
                if (!empty($validated['customer_phone'])) $shipmentData['recipient_phone'] = $validated['customer_phone'];
                
                $addressParts = [];
                if (isset($validated['street'])) $addressParts[] = $validated['street'];
                if (isset($validated['city'])) $addressParts[] = $validated['city'];
                if (isset($validated['province'])) $addressParts[] = $validated['province'];

                if (!empty($addressParts)) {
                    $shipmentData['address'] = implode(', ', array_filter($addressParts));
                }
                if (isset($validated['city'])) $shipmentData['city'] = $validated['city'];
                if (isset($validated['province'])) $shipmentData['region'] = $validated['province'];

                if (!empty($shipmentData)) {
                    $shipment->update($shipmentData);
                }
            }

            // 4. Update items and recalculate total
            if (isset($validated['items'])) {
                // Delete existing items
                $order->items()->delete();

                $subtotal = 0.00;
                foreach ($validated['items'] as $itemData) {
                    $product  = Product::findOrFail($itemData['product_id']);
                    $qty      = (int) $itemData['quantity'];

                    $unitPrice = 0;
                    if (!empty($product->variants)) {
                        $variants  = is_array($product->variants) ? $product->variants : json_decode($product->variants, true);
                        $unitPrice = (float) ($variants[0]['price'] ?? $product->cost ?? 0);
                    } else {
                        $unitPrice = (float) ($product->cost ?? 0);
                    }

                    $lineTotal = $unitPrice * $qty;
                    $subtotal += $lineTotal;

                    OrderItem::create([
                        'order_id'     => $order->id,
                        'product_id'   => $product->id,
                        'product_name' => $product->title,
                        'quantity'     => $qty,
                        'unit_price'   => $unitPrice,
                        'total_price'  => $lineTotal,
                    ]);
                }

                $shippingPrice = isset($validated['shipping_price']) ? (float)$validated['shipping_price'] : ($order->shipping_price ?? 0);
                $orderData['total_price'] = $subtotal + $shippingPrice;
            } else if (isset($validated['shipping_price'])) {
                // recalculate total price with new shipping price
                $subtotal = $order->items()->sum('total_price');
                $orderData['total_price'] = $subtotal + (float)$validated['shipping_price'];
            }

            if (!empty($orderData)) {
                $order->update($orderData);
            }
        });

        // Log status change in history
        if (isset($validated['status']) && $validated['status'] !== $oldStatus) {
            OrderHistory::create([
                'order_id'    => $order->id,
                'user_id'     => $request->user()->id,
                'action_type' => 'status_changed',
                'old_value'   => $oldStatus,
                'new_value'   => $validated['status'],
                'description' => "Statut changé de '{$oldStatus}' à '{$validated['status']}'.",
            ]);
        }

        $order->load(['items.product', 'assignedAgent', 'histories.user', 'shipments', 'client']);

        return response()->json([
            'message' => 'Commande mise à jour avec succès.',
            'order'   => $order,
        ]);
    }

    /**
     * Manually assign an agent (admin only).
     */
    public function assign(Request $request, string $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $agentId     = $validated['assigned_to'];
        $oldAgentName = $order->assignedAgent?->name ?? 'Non assigné';

        $order->updateQuietly(['assigned_to' => $agentId]);

        $newAgent     = $agentId ? User::find($agentId) : null;
        $newAgentName = $newAgent?->name ?? 'Non assigné';

        OrderHistory::create([
            'order_id'    => $order->id,
            'user_id'     => $request->user()->id,
            'action_type' => 'assigned',
            'old_value'   => $oldAgentName,
            'new_value'   => $newAgentName,
            'description' => "Assigné de '{$oldAgentName}' à '{$newAgentName}'.",
        ]);

        $order->load(['items.product', 'assignedAgent']);

        return response()->json([
            'message' => 'Agent assigné avec succès.',
            'order'   => $order,
        ]);
    }

    /**
     * Stub for syncing abandoned orders.
     */
    public function syncAbandoned(Request $request)
    {
        return response()->json([
            'message' => 'Shopify sync is not configured yet.',
        ]);
    }
}