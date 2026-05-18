<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductService
{
    /**
     * Get products with filters and pagination.
     */
    public function getProducts(array $filters = []): LengthAwarePaginator
    {
        $query = Product::query();

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);
        
        $perPage = $filters['per_page'] ?? 15;
        
        return $query->paginate($perPage);
    }

    /**
     * Get single product by ID or handle.
     */
    public function getProduct($identifier): ?Product
    {
        if (is_numeric($identifier)) {
            return Product::find($identifier);
        }
        
        return Product::where('handle', $identifier)->first();
    }

    /**
     * Create a new product.
     */
    public function createProduct(array $data): Product
    {
        DB::beginTransaction();
        
        try {
            // Generate handle if not provided
            if (empty($data['handle'])) {
                $data['handle'] = Str::slug($data['title']);
            }
            
            // Ensure handle is unique
            $data['handle'] = $this->makeUniqueHandle($data['handle']);
            
            // Process tags (convert comma-separated string to array)
            if (isset($data['tags_string'])) {
                $data['tags'] = array_map('trim', explode(',', $data['tags_string']));
                unset($data['tags_string']);
            }
            
            // Process tags if provided as string directly
            if (isset($data['tags']) && is_string($data['tags'])) {
                $data['tags'] = array_map('trim', explode(',', $data['tags']));
            }
            
            // Set default values
            $data['source_type'] = $data['source_type'] ?? 'manual';
            $data['status'] = $data['status'] ?? 'draft';
            
            // Process variants
            if (isset($data['variants'])) {
                if (is_string($data['variants'])) {
                    $data['variants'] = json_decode($data['variants'], true);
                }
                
                // Ensure each variant has required fields
                foreach ($data['variants'] as &$variant) {
                    $variant['price'] = floatval($variant['price'] ?? 0);
                    $variant['stock'] = intval($variant['stock'] ?? 0);
                    $variant['compare_at_price'] = isset($variant['compare_at_price']) ? floatval($variant['compare_at_price']) : null;
                    $variant['cost'] = isset($variant['cost']) ? floatval($variant['cost']) : null;
                }
            }
            
            $product = Product::create($data);
            
            DB::commit();
            
            return $product;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing product.
     */
    public function updateProduct(Product $product, array $data): Product
    {
        DB::beginTransaction();
        
        try {
            // Handle slug update
            if (isset($data['handle'])) {
                $data['handle'] = $this->makeUniqueHandle($data['handle'], $product->id);
            }
            
            // Process tags
            if (isset($data['tags_string'])) {
                $data['tags'] = array_map('trim', explode(',', $data['tags_string']));
                unset($data['tags_string']);
            }
            
            // Process tags if provided as string directly
            if (isset($data['tags']) && is_string($data['tags'])) {
                $data['tags'] = array_map('trim', explode(',', $data['tags']));
            }
            
            // Process variants
            if (isset($data['variants'])) {
                if (is_string($data['variants'])) {
                    $data['variants'] = json_decode($data['variants'], true);
                }
                
                // Ensure each variant has required fields
                foreach ($data['variants'] as &$variant) {
                    $variant['price'] = floatval($variant['price'] ?? 0);
                    $variant['stock'] = intval($variant['stock'] ?? 0);
                    $variant['compare_at_price'] = isset($variant['compare_at_price']) ? floatval($variant['compare_at_price']) : null;
                    $variant['cost'] = isset($variant['cost']) ? floatval($variant['cost']) : null;
                }
            }
            
            $product->update($data);
            
            DB::commit();
            
            return $product->fresh();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a product.
     */
    public function deleteProduct(Product $product): bool
    {
        return $product->delete();
    }

    /**
     * Bulk delete products.
     */
    public function bulkDeleteProducts(array $ids): int
    {
        return Product::whereIn('id', $ids)->delete();
    }

    /**
     * Bulk update status for products.
     */
    public function bulkUpdateStatus(array $ids, string $status): int
    {
        return Product::whereIn('id', $ids)->update(['status' => $status]);
    }

    /**
     * Get products summary.
     */
    public function getSummary(?int $shopId = null): array
    {
        $query = Product::query();
        
        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $totalQuery = clone $query;
        $activeQuery = clone $query;
        $draftQuery = clone $query;
        $archivedQuery = clone $query;
        $manualQuery = clone $query;
        $shopifyQuery = clone $query;
        $outOfStockQuery = clone $query;

        return [
            'total' => $totalQuery->count(),
            'active' => $activeQuery->where('status', 'active')->count(),
            'draft' => $draftQuery->where('status', 'draft')->count(),
            'archived' => $archivedQuery->where('status', 'archived')->count(),
            'manual' => $manualQuery->where('source_type', 'manual')->count(),
            'shopify' => $shopifyQuery->where('source_type', 'shopify')->count(),
            'out_of_stock' => $outOfStockQuery->where(function($q) {
                $q->where('stock', 0)
                  ->orWhereJsonContains('variants', ['stock' => 0]);
            })->count(),
            'low_stock' => $this->getLowStockCount($shopId),
            'total_value' => $this->getTotalInventoryValue($shopId),
        ];
    }

    /**
     * Get low stock products count.
     */
    private function getLowStockCount(?int $shopId = null): int
    {
        $query = Product::query();
        
        if ($shopId) {
            $query->where('shop_id', $shopId);
        }
        
        // This is a simplified version - you might need to adjust based on your variant structure
        return $query->where(function($q) {
            $q->where('stock', '>', 0)
              ->where('stock', '<=', 5);
        })->count();
    }

    /**
     * Get total inventory value.
     */
    private function getTotalInventoryValue(?int $shopId = null): float
    {
        $products = Product::query()
            ->when($shopId, function($q) use ($shopId) {
                $q->where('shop_id', $shopId);
            })
            ->get();
        
        $total = 0;
        foreach ($products as $product) {
            if ($product->variants) {
                foreach ($product->variants as $variant) {
                    $total += ($variant['price'] ?? 0) * ($variant['stock'] ?? 0);
                }
            }
        }
        
        return $total;
    }

    /**
     * Duplicate a product.
     */
    public function duplicateProduct(Product $product): Product
    {
        $newProduct = $product->replicate();
        $newProduct->title = $product->title . ' (Copy)';
        $newProduct->handle = $this->makeUniqueHandle($product->handle . '-copy');
        $newProduct->status = 'draft';
        $newProduct->save();
        
        return $newProduct;
    }

    /**
     * Get products by tag.
     */
    public function getProductsByTag(string $tag, ?int $shopId = null): LengthAwarePaginator
    {
        $query = Product::whereJsonContains('tags', $tag);
        
        if ($shopId) {
            $query->where('shop_id', $shopId);
        }
        
        return $query->paginate(15);
    }

    /**
     * Search products.
     */
    public function searchProducts(string $searchTerm, ?int $shopId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query();
        
        if ($shopId) {
            $query->where('shop_id', $shopId);
        }
        
        $query->where(function ($q) use ($searchTerm) {
            $q->where('title', 'like', "%{$searchTerm}%")
              ->orWhere('handle', 'like', "%{$searchTerm}%")
              ->orWhere('vendor', 'like', "%{$searchTerm}%")
              ->orWhere('product_type', 'like', "%{$searchTerm}%")
              ->orWhereJsonContains('tags', $searchTerm);
        });
        
        return $query->paginate($perPage);
    }

    /**
     * Make a unique handle.
     */
    private function makeUniqueHandle(string $handle, ?int $excludeId = null): string
    {
        $originalHandle = $handle;
        $counter = 1;
        
        while ($this->handleExists($handle, $excludeId)) {
            $handle = $originalHandle . '-' . $counter;
            $counter++;
        }
        
        return $handle;
    }

    /**
     * Check if handle exists.
     */
    private function handleExists(string $handle, ?int $excludeId = null): bool
    {
        $query = Product::where('handle', $handle);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Apply filters to query.
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        // Filter by shop
        if (!empty($filters['shop_id'])) {
            $query->where('shop_id', $filters['shop_id']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by source type
        if (!empty($filters['source_type'])) {
            $query->where('source_type', $filters['source_type']);
        }
        
        // Filter by vendor
        if (!empty($filters['vendor'])) {
            $query->where('vendor', 'like', "%{$filters['vendor']}%");
        }
        
        // Filter by product type
        if (!empty($filters['product_type'])) {
            $query->where('product_type', 'like', "%{$filters['product_type']}%");
        }

        // Search by title, handle, vendor, or tags
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('handle', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%")
                  ->orWhere('product_type', 'like', "%{$search}%")
                  ->orWhereJsonContains('tags', $search);
            });
        }
        
        // Filter by specific tag
        if (!empty($filters['tag'])) {
            $query->whereJsonContains('tags', $filters['tag']);
        }
        
        // Filter by price range
        if (!empty($filters['min_price'])) {
            $query->where(function($q) use ($filters) {
                $q->where('price', '>=', $filters['min_price'])
                  ->orWhereJsonContains('varients', ['price' => $filters['min_price']]);
            });
        }
        
        // Filter by stock status
        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'in_stock') {
                $query->where(function($q) {
                    $q->where('stock', '>', 0)
                      ->orWhereJsonContains('variants', ['stock' => ['>', 0]]);
                });
            } elseif ($filters['stock_status'] === 'out_of_stock') {
                $query->where(function($q) {
                    $q->where('stock', 0)
                      ->orWhereJsonContains('variants', ['stock' => 0]);
                });
            } elseif ($filters['stock_status'] === 'low_stock') {
                $query->where(function($q) {
                    $q->where('stock', '>', 0)
                      ->where('stock', '<=', 5);
                });
            }
        }
    }

    /**
     * Apply sorting to query.
     */
    private function applySorting(Builder $query, array $filters): void
    {
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        
        // Allow sorting by custom fields
        $allowedSortFields = ['created_at', 'updated_at', 'title', 'price', 'stock', 'status', 'vendor'];
        
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }
}