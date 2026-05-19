<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function getProducts(array $filters = []): LengthAwarePaginator
    {
        $query = Product::query();

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    public function getProduct($identifier): ?Product
    {
        if (is_numeric($identifier)) {
            return Product::find($identifier);
        }

        return Product::where('handle', $identifier)->first();
    }

    public function createProduct(array $data): Product
    {
        DB::beginTransaction();

        try {
            if (empty($data['handle'])) {
                $data['handle'] = Str::slug($data['title']);
            }

            $data['handle'] = $this->makeUniqueHandle($data['handle']);

            if (isset($data['tags_string'])) {
                $data['tags'] = array_map('trim', explode(',', $data['tags_string']));
                unset($data['tags_string']);
            }

            if (isset($data['tags']) && is_string($data['tags'])) {
                $data['tags'] = array_map('trim', explode(',', $data['tags']));
            }

            $data['source_type'] = $data['source_type'] ?? 'manual';
            $data['status'] = $data['status'] ?? 'draft';

            if (isset($data['variants'])) {
                if (is_string($data['variants'])) {
                    $data['variants'] = json_decode($data['variants'], true);
                }

                if (is_array($data['variants'])) {
                    foreach ($data['variants'] as &$variant) {
                        $variant['price'] = floatval($variant['price'] ?? 0);
                        $variant['stock'] = intval($variant['stock'] ?? 0);
                        $variant['compare_at_price'] = isset($variant['compare_at_price']) ? floatval($variant['compare_at_price']) : null;
                        $variant['cost'] = isset($variant['cost']) ? floatval($variant['cost']) : null;
                    }
                }
            }

            if (isset($data['cost'])) {
                $data['cost'] = floatval($data['cost']);
            }

            $product = Product::create($data);

            DB::commit();

            return $product;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateProduct(Product $product, array $data): Product
    {
        DB::beginTransaction();

        try {
            if (isset($data['handle'])) {
                $data['handle'] = $this->makeUniqueHandle($data['handle'], $product->id);
            }

            if (isset($data['tags_string'])) {
                $data['tags'] = array_map('trim', explode(',', $data['tags_string']));
                unset($data['tags_string']);
            }

            if (isset($data['tags']) && is_string($data['tags'])) {
                $data['tags'] = array_map('trim', explode(',', $data['tags']));
            }

            if (isset($data['variants'])) {
                if (is_string($data['variants'])) {
                    $data['variants'] = json_decode($data['variants'], true);
                }

                if (is_array($data['variants'])) {
                    foreach ($data['variants'] as &$variant) {
                        $variant['price'] = floatval($variant['price'] ?? 0);
                        $variant['stock'] = intval($variant['stock'] ?? 0);
                        $variant['compare_at_price'] = isset($variant['compare_at_price']) ? floatval($variant['compare_at_price']) : null;
                        $variant['cost'] = isset($variant['cost']) ? floatval($variant['cost']) : null;
                    }
                }
            }

            if (isset($data['cost'])) {
                $data['cost'] = floatval($data['cost']);
            }

            $product->update($data);

            DB::commit();

            return $product->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteProduct(Product $product): bool
    {
        return $product->delete();
    }

    public function bulkDeleteProducts(array $ids): int
    {
        return Product::whereIn('id', $ids)->delete();
    }

    public function bulkUpdateStatus(array $ids, string $status): int
    {
        return Product::whereIn('id', $ids)->update(['status' => $status]);
    }

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
            'out_of_stock' => $outOfStockQuery->where(function ($q) {
                $q->where('stock', 0)
                    ->orWhereJsonContains('variants', ['stock' => 0]);
            })->count(),
            'low_stock' => $this->getLowStockCount($shopId),
            'total_value' => $this->getTotalInventoryValue($shopId),
        ];
    }

    private function getLowStockCount(?int $shopId = null): int
    {
        $query = Product::query();

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        return $query->where(function ($q) {
            $q->where('stock', '>', 0)
                ->where('stock', '<=', 5);
        })->count();
    }

    private function getTotalInventoryValue(?int $shopId = null): float
    {
        $products = Product::query()
            ->when($shopId, function ($q) use ($shopId) {
                $q->where('shop_id', $shopId);
            })
            ->get();

        $total = 0;
        foreach ($products as $product) {
            if ($product->variants && is_array($product->variants)) {
                foreach ($product->variants as $variant) {
                    $total += (floatval($variant['price'] ?? 0)) * (intval($variant['stock'] ?? 0));
                }
            }
        }

        return $total;
    }

    public function duplicateProduct(Product $product): Product
    {
        $newProduct = $product->replicate();
        $newProduct->title = $product->title . ' (Copy)';
        $newProduct->handle = $this->makeUniqueHandle($product->handle . '-copy');
        $newProduct->status = 'draft';
        $newProduct->save();

        return $newProduct;
    }

    public function getProductsByTag(string $tag, ?int $shopId = null): LengthAwarePaginator
    {
        $query = Product::whereJsonContains('tags', $tag);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        return $query->paginate(15);
    }

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

    private function handleExists(string $handle, ?int $excludeId = null): bool
    {
        $query = Product::where('handle', $handle);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['shop_id'])) {
            $query->where('shop_id', $filters['shop_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['source_type'])) {
            $query->where('source_type', $filters['source_type']);
        }

        if (!empty($filters['vendor'])) {
            $query->where('vendor', 'like', "%{$filters['vendor']}%");
        }

        if (!empty($filters['product_type'])) {
            $query->where('product_type', 'like', "%{$filters['product_type']}%");
        }

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

        if (!empty($filters['tag'])) {
            $query->whereJsonContains('tags', $filters['tag']);
        }

        if (!empty($filters['min_price'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('price', '>=', $filters['min_price'])
                    ->orWhereJsonContains('variants', ['price' => $filters['min_price']]);
            });
        }

        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'in_stock') {
                $query->where(function ($q) {
                    $q->where('stock', '>', 0)
                        ->orWhereJsonContains('variants', ['stock' => ['>', 0]]);
                });
            } elseif ($filters['stock_status'] === 'out_of_stock') {
                $query->where(function ($q) {
                    $q->where('stock', 0)
                        ->orWhereJsonContains('variants', ['stock' => 0]);
                });
            } elseif ($filters['stock_status'] === 'low_stock') {
                $query->where(function ($q) {
                    $q->where('stock', '>', 0)
                        ->where('stock', '<=', 5);
                });
            }
        }
    }

    private function applySorting(Builder $query, array $filters): void
    {
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        $allowedSortFields = ['created_at', 'updated_at', 'title', 'price', 'stock', 'status', 'vendor'];

        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }
}
