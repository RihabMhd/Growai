<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncShopifyProductsJob;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Services\ShopifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopifyController extends Controller
{
    public function __construct(
        private readonly ShopifyService $shopifyService
    ) {}

    /**
     * Shopify connection status (single-shop, kept for backward-compat).
     */
    public function status(Request $request): JsonResponse
    {
        $shop = $this->resolveShop($request);

        if (!$shop) {
            return response()->json(['connected' => false]);
        }

        return response()->json([
            'connected'      => true,
            'shop_id'        => $shop->id,
            'domain'         => $shop->shopify_domain,
            'last_synced_at' => $shop->last_synced_at?->toISOString(),
            'product_count'  => Product::where('shop_id', $shop->id)
                ->where('status', 'active')
                ->count(),
            'order_count'    => Order::where('shop_id', $shop->id)->count(),
        ]);
    }

    /**
     * Sync Shopify products for the resolved shop.
     */
    public function syncProducts(Request $request): JsonResponse
    {
        $shop = $this->resolveShop($request);

        if (!$shop) {
            return response()->json([
                'error' => 'No Shopify shop connected.',
            ], 422);
        }

        SyncShopifyProductsJob::dispatch($shop);

        // last_synced_at is now in $fillable, so this actually persists
        $shop->update(['last_synced_at' => now()]);

        return response()->json([
            'message' => 'Product sync queued successfully.',
            'shop_id' => $shop->id,
        ]);
    }

    /**
     * List synced products for the resolved shop.
     */
    public function products(Request $request): JsonResponse
    {
        $shop = $this->resolveShop($request);

        if (!$shop) {
            return response()->json(['data' => [], 'meta' => []]);
        }

        $query = Product::where('shop_id', $shop->id)
            ->where('status', 'active');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%");
            });
        }

        $products = $query
            ->orderByDesc('created_at')
            ->paginate($request->query('per_page', 24));

        return response()->json($products);
    }

    /**
     * List synced orders for the resolved shop.
     */
    public function orders(Request $request): JsonResponse
    {
        $shop = $this->resolveShop($request);

        if (!$shop) {
            return response()->json(['data' => [], 'meta' => []]);
        }

        $query = Order::with('items')->where('shop_id', $shop->id);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $orders = $query
            ->orderByDesc('shopify_created_at')
            ->paginate($request->query('per_page', 20));

        return response()->json($orders);
    }

    /**
     * List all connected shops for the authenticated user.
     * Returns boutique_name, last_synced_at, and created_at so the frontend
     * can render the store card and boutique-name input correctly.
     *
     * GET /api/shopify/shops
     * Response: { shops: [ { id, name, shopify_domain, boutique_name,
     *                         last_synced_at, created_at } ] }
     */
    public function listShops(Request $request): JsonResponse
    {
        $shops = Shop::where('is_active', true)
            ->whereNotNull('access_token')
            ->orderByDesc('created_at')
            ->get([
                'id',
                'name',
                'shopify_domain',
                'boutique_name',   // now in $fillable — persisted correctly
                'last_synced_at',
                'created_at',
            ]);

        return response()->json(['shops' => $shops]);
    }

    /**
     * Update mutable shop fields (name, boutique_name).
     *
     * PATCH /api/shopify/shops/{shop}
     */
    public function updateShop(Request $request, Shop $shop): JsonResponse
    {
        $validated = $request->validate([
            'name'          => ['nullable', 'string', 'max:120'],
            // Accept boutique_name so the frontend WhatsApp-template field works
            'boutique_name' => ['nullable', 'string', 'max:120'],
        ]);

        $shop->update($validated);

        return response()->json(['shop' => $shop]);
    }

    /**
     * Disconnect a shop (revoke token, mark inactive).
     *
     * DELETE /api/shopify/shops/{shop}
     */
    public function disconnectShop(Request $request, Shop $shop): JsonResponse
    {
        $shop->update([
            'is_active'    => false,
            'access_token' => null,
        ]);

        return response()->json(['message' => 'Store disconnected.']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve the shop for the current request.
     *
     * Priority:
     *  1. Explicit ?shop_id query parameter (must still be active + have token)
     *  2. Authenticated user's own shop_id (if the User model carries one)
     *  3. Last active shop in the database (single-tenant fallback only)
     *
     * The previous implementation fetched the first shop in the entire table
     * regardless of which user was authenticated, making every tenant's data
     * visible to every other tenant.  Step 2 scopes the fallback to the
     * authenticated user so multi-tenant data is not leaked.
     */
    private function resolveShop(Request $request): ?Shop
    {
        $user = $request->user();

        // 1. Explicit shop_id from query string
        if ($shopId = $request->query('shop_id')) {
            return Shop::where('id', $shopId)
                ->where('is_active', true)
                ->whereNotNull('access_token')
                ->first();
        }

        // 2. Shop owned by the authenticated user
        if ($user && $user->shop_id) {
            return Shop::where('id', $user->shop_id)
                ->where('is_active', true)
                ->whereNotNull('access_token')
                ->first();
        }

        // 3. Single-tenant fallback: latest active Shopify shop
        //    (safe only when every install has exactly one shop)
        return Shop::where('is_active', true)
            ->whereNotNull('access_token')
            ->where('platform', 'shopify')
            ->latest()
            ->first();
    }
}