<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\OrderHistory;
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
            ->with(['items.product', 'client', 'shop', 'assignedAgent'])
            ->latest();

        // 1. Apply role-based visibility restrictions
        if ($user->role === 'staff') {
            $assignedProductIds = $user->products()->pluck('products.id')->toArray();
            
            // If the agent has assigned products: only see orders containing at least one of their assigned products
            if (count($assignedProductIds) > 0) {
                $query->whereHas('items', function ($itemQuery) use ($assignedProductIds) {
                    $itemQuery->whereIn('product_id', $assignedProductIds);
                });
            }
            // If they have no assigned products: they see all orders
        }

        // 2. Filter by Search Query (name, phone, order number)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        // 3. Filter by Type (abandoned vs normal)
        $isAbandoned = $request->input('type') === 'abandoned';
        $query->where('is_abandoned', $isAbandoned);

        // 4. Filter by Status Tab
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // 5. Fetch all matching orders
        $orders = $query->get();

        // 6. Calculate premium metric summary based ONLY on visible orders
        $totalOrders = $orders->count();
        $confirmedCount = $orders->filter(fn($o) => in_array($o->status, ['confirmed', 'delivered', 'processing', 'shipped']))->count();
        $cancelledCount = $orders->filter(fn($o) => in_array($o->status, ['cancelled', 'returned']))->count();
        $pendingCount = $orders->filter(fn($o) => $o->status === 'pending')->count();
        
        $confirmationRate = $totalOrders > 0 
            ? round(($confirmedCount / $totalOrders) * 100) 
            : 0;

        return response()->json([
            'orders' => $orders,
            'metrics' => [
                'total_orders' => $totalOrders,
                'confirmed' => $confirmedCount,
                'cancelled' => $cancelledCount,
                'pending' => $pendingCount,
                'confirmation_rate' => $confirmationRate . '%'
            ],
            // For admin's manual assignment dropdown, return active agents
            'active_agents' => $user->role === 'admin' 
                ? User::where('role', 'staff')->where('is_active', true)->get() 
                : []
        ]);
    }

    /**
     * Store a newly created order (triggers auto-dispatch).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'is_abandoned' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        // Find default client and shop (or fallback)
        $client = \App\Models\Client::first();
        if (!$client) {
            $client = \App\Models\Client::create([
                'name' => 'Client Par Défaut',
                'email' => 'client@growai.com'
            ]);
        }

        $shop = \App\Models\Shop::first();
        if (!$shop) {
            $shop = \App\Models\Shop::create([
                'client_id' => $client->id,
                'name' => 'Boutique Flash',
                'url' => 'https://shopify.flashmanager.com'
            ]);
        }

        // Run in transaction
        $order = DB::transaction(function () use ($validated, $client, $shop) {
            // 1. Create order skeleton
            $order = Order::create([
                'shop_id' => $shop->id,
                'client_id' => $client->id,
                'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'total_price' => 0.00,
                'shipping_price' => 10.00, // standard shipping fee
                'discount' => 0.00,
                'currency' => 'DA',
                'status' => 'pending',
                'financial_status' => 'unpaid',
                'notes' => $validated['notes'] ?? null,
                'is_abandoned' => $validated['is_abandoned'] ?? false,
                'abandoned_at' => ($validated['is_abandoned'] ?? false) ? now() : null
            ]);

            // 2. Create order items and aggregate price
            $totalPrice = 0.00;
            foreach ($validated['items'] as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                $qty = (int) $itemData['quantity'];
                $itemTotal = (float) $product->price * $qty;
                $totalPrice += $itemTotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $product->price,
                    'total_price' => $itemTotal
                ]);
            }

            // 3. Save final prices (order items + shipping)
            $order->update([
                'total_price' => $totalPrice + 10.00
            ]);

            return $order;
        });

        // Load relations and observer changes
        $order->load(['items.product', 'assignedAgent']);

        return response()->json([
            'message' => 'Commande créée avec succès !',
            'order' => $order
        ], 201);
    }

    /**
     * Display the specified order details with history.
     */
    public function show(string $id)
    {
        $order = Order::with(['items.product', 'client', 'shop', 'assignedAgent', 'histories.user'])
            ->findOrFail($id);

        return response()->json([
            'order' => $order
        ]);
    }

    /**
     * Update the order status (triggers commission payout).
     */
    public function update(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'status' => 'nullable|string|in:pending,confirmed,processing,shipped,delivered,cancelled,returned',
            'financial_status' => 'nullable|string|in:unpaid,pending,paid,refunded',
            'notes' => 'nullable|string'
        ]);

        $order->update($validated);
        $order->load(['items.product', 'assignedAgent', 'histories.user']);

        return response()->json([
            'message' => 'Commande mise à jour avec succès.',
            'order' => $order
        ]);
    }

    /**
     * Manually assign an agent to the order (Admin only).
     */
    public function assign(Request $request, string $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé. Seuls les administrateurs peuvent assigner des agents.'], 403);
        }

        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id'
        ]);

        $agentId = $validated['assigned_to'];
        $oldAgentName = $order->assignedAgent ? $order->assignedAgent->name : 'Non assigné';
        
        $order->updateQuietly([
            'assigned_to' => $agentId
        ]);

        $newAgent = $agentId ? User::find($agentId) : null;
        $newAgentName = $newAgent ? $newAgent->name : 'Non assigné';

        // Add history log
        OrderHistory::create([
            'order_id' => $order->id,
            'user_id' => $request->user()->id,
            'action_type' => 'status',
            'old_value' => $oldAgentName,
            'new_value' => $newAgentName,
            'description' => "Commande assignée manuellement par l'administrateur de '{$oldAgentName}' à '{$newAgentName}'."
        ]);

        $order->load(['items.product', 'assignedAgent']);

        return response()->json([
            'message' => 'Agent assigné avec succès.',
            'order' => $order
        ]);
    }
}
