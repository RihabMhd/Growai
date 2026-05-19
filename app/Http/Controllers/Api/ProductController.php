<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Shop;
class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    private function resolveShop(): Shop
    {
        $user = auth()->user();
        $shop = Shop::where('team_id', $user->team_id)
            ->where('is_active', true)
            ->first();

        if (!$shop) {
            abort(403, 'No active shop found for your account.');
        }

        return $shop;
    }

    public function index(Request $request)
    {
        $shop = $this->resolveShop();

        $filters = $request->only([
            'status', 'source_type', 'vendor',
            'product_type', 'search', 'tag',
            'sort_by', 'sort_order', 'per_page',
            'min_price', 'stock_status'
        ]);

        $filters['shop_id'] = $shop->id; 

        $products = $this->productService->getProducts($filters);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'total' => $products->total(),
        ]);
    }

    public function store(Request $request)
    {
        $shop = $this->resolveShop();

        $validated = $this->validateProduct($request);
        $validated['shop_id'] = $shop->id; // overwrite whatever frontend sent

        $product = $this->productService->createProduct($validated);
        $product->refresh(); // Ensure all accessors are loaded

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product,
            'product' => $product // Also send as 'product' for consistency
        ], 201);
    }

    public function show($identifier)
    {
        try {
            $product = $this->productService->getProduct($identifier);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'error' => 'Product not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $product
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Product not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch product',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $shop = $this->resolveShop();

        // Ensure the product belongs to this shop
        $product = Product::where('id', $id)
                          ->where('shop_id', $shop->id)
                          ->firstOrFail();

        $validated = $this->validateProduct($request, $product);
        $validated['shop_id'] = $shop->id;

        $product = $this->productService->updateProduct($product, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product
        ]);
    }

     public function destroy($id)
    {
        $shop = $this->resolveShop();

        $product = Product::where('id', $id)
                          ->where('shop_id', $shop->id)
                          ->firstOrFail();

        $this->productService->deleteProduct($product);

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    public function summary(Request $request)
    {
        try {
            $shopId = $request->get('shop_id');
            $summary = $this->productService->getSummary($shopId);

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get summary',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function duplicate($id)
    {
        try {
            $product = $this->productService->getProduct($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'error' => 'Product not found'
                ], 404);
            }

            $newProduct = $this->productService->duplicateProduct($product);

            return response()->json([
                'success' => true,
                'message' => 'Product duplicated successfully',
                'data' => $newProduct
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to duplicate product',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkDestroy(Request $request)
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:products,id'
            ]);

            $count = $this->productService->bulkDeleteProducts($validated['ids']);

            return response()->json([
                'success' => true,
                'message' => "{$count} products deleted successfully"
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete products',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkUpdateStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:products,id',
                'status' => 'required|in:active,draft,archived'
            ]);

            $count = $this->productService->bulkUpdateStatus($validated['ids'], $validated['status']);

            return response()->json([
                'success' => true,
                'message' => "{$count} products updated successfully"
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update products',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getByTag(Request $request, $tag)
    {
        try {
            $shopId = $request->get('shop_id');
            $products = $this->productService->getProductsByTag($tag, $shopId);

            return response()->json([
                'success' => true,
                'data' => $products->items(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch products',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $validated = $request->validate([
                'q' => 'required|string|min:2',
                'shop_id' => 'nullable|exists:shops,id',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $shopId = $validated['shop_id'] ?? null;
            $perPage = $validated['per_page'] ?? 15;

            $products = $this->productService->searchProducts($validated['q'], $shopId, $perPage);

            return response()->json([
                'success' => true,
                'data' => $products->items(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to search products',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $uniqueRule = $product
            ? 'unique:products,handle,' . $product->id
            : 'unique:products,handle';

        $rules = [
            'title' => 'required|string|max:255',
            'vendor' => 'nullable|string|max:255',
            'product_type' => 'nullable|string|max:255',
            'handle' => 'nullable|string|max:255|' . $uniqueRule,
            'status' => 'in:active,draft,archived',
            'tags' => 'nullable|string',
            'tags_string' => 'nullable|string',
            'image' => 'nullable|string|max:500',
            'images' => 'nullable|array',
            'description' => 'nullable|string',
            'variants' => 'nullable|array',
            'cost' => 'nullable|numeric|min:0',
            'source_type' => 'in:manual,shopify',
        ];

        return $request->validate($rules);
    }
}
