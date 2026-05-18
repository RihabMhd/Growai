<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
        
        // Apply middleware for authentication if needed
        // $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    /**
     * Display a listing of all products.
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only([
                'shop_id', 'status', 'source_type', 'vendor', 
                'product_type', 'search', 'tag', 'sort_by', 
                'sort_order', 'per_page', 'min_price', 'max_price',
                'stock_status'
            ]);
            
            $products = $this->productService->getProducts($filters);
            
            return response()->json([
                'success' => true,
                'data' => $products->items(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
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

    /**
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        try {
            $validated = $this->validateProduct($request);
            
            $product = $this->productService->createProduct($validated);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product
            ], 201);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create product',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified product.
     */
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

    /**
     * Update the specified product.
     */
    public function update(Request $request, $id)
    {
        try {
            $product = $this->productService->getProduct($id);
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'error' => 'Product not found'
                ], 404);
            }
            
            $validated = $this->validateProduct($request, $product);
            
            $product = $this->productService->updateProduct($product, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update product',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified product.
     */
    public function destroy($id)
    {
        try {
            $product = $this->productService->getProduct($id);
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'error' => 'Product not found'
                ], 404);
            }
            
            $this->productService->deleteProduct($product);
            
            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete product',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products summary.
     */
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

    /**
     * Duplicate a product.
     */
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

    /**
     * Bulk delete products.
     */
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

    /**
     * Bulk update status.
     */
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

    /**
     * Get products by tag.
     */
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

    /**
     * Search products.
     */
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

    /**
     * Validate product data.
     */
    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $uniqueRule = $product 
            ? 'unique:products,handle,' . $product->id
            : 'unique:products,handle';
            
        $rules = [
            'shop_id' => 'required|exists:shops,id',
            'title' => 'required|string|max:255',
            'vendor' => 'nullable|string|max:255',
            'product_type' => 'nullable|string|max:255',
            'handle' => 'nullable|string|max:255|' . $uniqueRule,
            'status' => 'in:active,draft,archived',
            'tags' => 'nullable|string',
            'tags_string' => 'nullable|string',
            'image' => 'nullable|url',
            'images' => 'nullable|array',
            'description' => 'nullable|string',
            'variants' => 'nullable|array',
            'source_type' => 'in:manual,shopify',
        ];
        
        return $request->validate($rules);
    }
}