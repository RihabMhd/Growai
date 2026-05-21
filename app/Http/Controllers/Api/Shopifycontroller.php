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
    public function __construct(private readonly ShopifyService $shopifyService) {}

    /**
     * Return connection status + basic stats for the authenticated shop.
     */
    public function status(Request $request): JsonResponse
    {
        $shop = $this->resolveShop($request);

        if (!$shop) {
            return response()->json(['connected' => false]);
        }

        $connected = $this->shopifyService->testConnection($shop);

        return response()->json([
            'connected'      => $connected,
            'domain'         => $shop->shopify_domain,
            'last_synced_at' => $shop->last_synced_at?->toISOString(),
            'product_count'  => Product::where('shop_id', $shop->id)->where('status', 'active')->count(),
            'order_count'    => Order::where('shop_id', $shop->id)->count(),
        ]);
    }

    /**
     * Dispatch a background sync job and return immediately.
     */
    public function syncProducts(Request $request): JsonResponse
    {
        $shop = $this->resolveShop($request);

        if (!$shop) {
            return response()->json(['error' => 'No Shopify shop connected.'], 422);
        }

        SyncShopifyProductsJob::dispatch($shop);

        return response()->json([
            'message' => 'Product sync queued successfully.',
            'shop_id' => $shop->id,
        ]);
    }

    /**
     * List synced products with pagination and basic search.
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
     * List synced orders with pagination and status filter.
     */
    public function orders(Request $request): JsonResponse
    {
        $shop = $this->resolveShop($request);

        if (!$shop) {
            return response()->json(['data' => [], 'meta' => []]);
        }

        $query = Order::with('items')
            ->where('shop_id', $shop->id);

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

    // -------------------------------------------------------------------------

    /**
     * Resolve the active Shopify shop for the authenticated user.
     * Adjust this if your User→Shop relationship differs.
     */
    private function resolveShop(Request $request): ?Shop
    {
        // Option A: if each user owns one shop
        // return $request->user()->shop;

        // Option B: shop_id passed as query param (multi-shop support)
        $shopId = $request->query('shop_id') ?? $request->user()?->shop_id;

        if (!$shopId) return null;

        return Shop::where('id', $shopId)
            ->where('is_active', true)
            ->whereNotNull('access_token')
            ->first();
    }
}