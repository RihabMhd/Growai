<?php

namespace App\Infrastructure\Products\Repositories;

use App\Domain\Products\Contracts\ProductRepositoryInterface;
use App\Domain\Products\DTOs\ProductData;
use App\Domain\Products\DTOs\ProductFilterData;
use App\Domain\Products\DTOs\ProductSummaryData;
use App\Domain\Products\DTOs\VariantData;
use App\Domain\Products\Exceptions\ProductNotFoundException;
use App\Domain\Products\Exceptions\ProductShopMismatchException;
use App\Domain\Products\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentProductRepository implements ProductRepositoryInterface
{
    // -------------------------------------------------------------------------
    // Single-record reads
    // -------------------------------------------------------------------------

    public function findById(int $id): ?Product
    {
        return Product::find($id);
    }

    public function findByIdAndShop(int $id, int $shopId): Product
    {
        $product = Product::find($id);

        if ($product === null) {
            throw new ProductNotFoundException($id);
        }

        if ($product->shop_id !== $shopId) {
            throw new ProductShopMismatchException($id, $shopId);
        }

        return $product;
    }

    public function findByHandle(string $handle): ?Product
    {
        return Product::where('handle', $handle)->first();
    }

    public function findByHandleAndShop(string $handle, int $shopId): ?Product
    {
        return Product::where('handle', $handle)
            ->where('shop_id', $shopId)
            ->first();
    }

    public function handleExistsInShop(string $handle, int $shopId, ?int $excludeId = null): bool
    {
        $query = Product::where('shop_id', $shopId)
            ->where('handle', $handle);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    // -------------------------------------------------------------------------
    // Multi-record reads
    // -------------------------------------------------------------------------

    public function findAllByShop(int $shopId, ProductFilterData $filters): LengthAwarePaginator
    {
        $builder = Product::where('products.shop_id', $shopId)
            ->select('products.*')
            ->when($filters->status,      fn($q) => $q->where('products.status',       $filters->status))
            ->when($filters->sourceType,  fn($q) => $q->where('products.source_type',  $filters->sourceType))
            ->when($filters->vendor,      fn($q) => $q->where('products.vendor',       $filters->vendor))
            ->when($filters->productType, fn($q) => $q->where('products.product_type', $filters->productType))
            ->when($filters->search, function ($q) use ($filters) {
                $term = '%' . $filters->search . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('products.title',        'like', $term)
                        ->orWhere('products.vendor',       'like', $term)
                        ->orWhere('products.product_type', 'like', $term)
                        ->orWhere('products.handle',       'like', $term);
                });
            })
            ->when($filters->tag, fn($q) => $q->whereJsonContains('products.tags', $filters->tag))
            ->when($filters->minPrice !== null, function ($q) use ($filters) {
                // JSON_EXTRACT works on MySQL; adjust for your DB if needed
                $q->whereRaw(
                    "JSON_EXTRACT(variants, '$[*].price') IS NOT NULL"
                )->whereRaw(
                    "JSON_CONTAINS(variants, JSON_OBJECT('price', CAST(? AS DECIMAL(10,2))))",
                    // This is approximate — JSON price range queries are imprecise in pure SQL
                    // Recommend filtering post-query in PHP if precision is required
                    [$filters->minPrice]
                );
            })
            ->when($filters->stockStatus, function ($q) use ($filters) {
                match ($filters->stockStatus) {
                    'out_of_stock' => $q->where('products.stock', '<=', 0),
                    'low_stock'    => $q->whereBetween('products.stock', [1, 5]),
                    'in_stock'     => $q->where('products.stock', '>', 5),
                    default        => null,
                };
            });

        $sortColumn = match ($filters->sortBy) {
            'price' => 'products.price',
            'stock' => 'products.stock',
            default => 'products.' . $filters->sortBy,
        };

        $builder->orderBy($sortColumn, $filters->sortOrder);

        return $builder->paginate($filters->perPage);
    }

    public function findByIdsAndShop(array $ids, int $shopId): Collection
    {
        return Product::where('shop_id', $shopId)
            ->whereIn('id', $ids)
            ->get();
    }

    public function findByTagAndShop(string $tag, int $shopId, int $perPage = 15): LengthAwarePaginator
    {
        return Product::where('shop_id', $shopId)
            ->whereJsonContains('tags', $tag)
            ->paginate($perPage);
    }

    public function searchByShop(string $term, int $shopId, int $perPage = 15): LengthAwarePaginator
    {
        $like = '%' . $term . '%';

        return Product::where('shop_id', $shopId)
            ->where(function ($q) use ($like, $term) {
                $q->where('title',        'like', $like)
                    ->orWhere('handle',       'like', $like)
                    ->orWhere('vendor',       'like', $like)
                    ->orWhere('product_type', 'like', $like)
                    ->orWhereJsonContains('tags', $term);
            })
            ->paginate($perPage);
    }

    public function getSummaryByShop(int $shopId): ProductSummaryData
    {
        $row = DB::table('products')
            ->where('shop_id', $shopId)
            ->selectRaw("
                COUNT(*)                                               AS total,
                SUM(CASE WHEN status       = 'active'   THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN status       = 'draft'    THEN 1 ELSE 0 END) AS draft,
                SUM(CASE WHEN status       = 'archived' THEN 1 ELSE 0 END) AS archived,
                SUM(CASE WHEN source_type  = 'manual'   THEN 1 ELSE 0 END) AS manual,
                SUM(CASE WHEN source_type  = 'shopify'  THEN 1 ELSE 0 END) AS shopify,
                SUM(CASE WHEN stock <= 0                THEN 1 ELSE 0 END) AS out_of_stock,
                SUM(CASE WHEN stock BETWEEN 1 AND 5     THEN 1 ELSE 0 END) AS low_stock,
                COALESCE(SUM(price * stock), 0)                        AS total_value
            ")
            ->first();

        return ProductSummaryData::fromArray((array) $row);
    }

    // -------------------------------------------------------------------------
    // Writes
    // -------------------------------------------------------------------------

    public function save(Product $product): Product
    {
        $product->save();

        return $product;
    }

    public function create(ProductData $data): Product
    {
        /** @var Product $product */
        $product = Product::create([
            'shop_id'      => $data->shopId,
            'title'        => $data->title,
            'status'       => $data->status,
            'source_type'  => $data->sourceType,
            'vendor'       => $data->vendor,
            'product_type' => $data->productType,
            'handle'       => $data->handle,
            'description'  => $data->description,
            'image'        => $data->image,
            'cost'         => $data->cost,
            'tags'         => $data->tags,
            'images'       => $data->images,
            'variants'     => array_map(fn(VariantData $v) => $v->toArray(), $data->variants),
        ]);

        return $product->refresh();
    }

    public function update(Product $product, ProductData $data): Product
    {
        $product->update([
            'title'        => $data->title,
            'status'       => $data->status      ?? $product->status,
            'source_type'  => $data->sourceType  ?? $product->source_type,   
            'vendor'       => $data->vendor      ?? $product->vendor,
            'product_type' => $data->productType ?? $product->product_type,
            'handle'       => $data->handle      ?? $product->handle,
            'description'  => $data->description ?? $product->description,
            'image'        => $data->image       ?? $product->image,
            'cost'         => $data->cost        ?? $product->getRawOriginal('cost'),
            'tags'         => $data->tags ?: $product->tags,
            'images'       => $data->images      ?? $product->images,        
            'variants'     => !empty($data->variants)
                ? array_map(fn(VariantData $v) => $v->toArray(), $data->variants)
                : $product->variants,
        ]);

        return $product->refresh();
    }



    public function delete(Product $product): bool
    {
        return (bool) $product->delete();
    }

    // -------------------------------------------------------------------------
    // Bulk operations — always shop-scoped
    // -------------------------------------------------------------------------

    public function bulkDeleteByShop(array $ids, int $shopId): int
    {
        return Product::where('shop_id', $shopId)
            ->whereIn('id', $ids)
            ->delete();
    }

    public function bulkUpdateStatusByShop(array $ids, string $status, int $shopId): int
    {
        return Product::where('shop_id', $shopId)
            ->whereIn('id', $ids)
            ->update(['status' => $status]);
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * Replace all variants for a product (delete-and-recreate).
     *
     * @param VariantData[] $variants
     */
    private function syncVariants(Product $product, array $variants): void
    {
        $product->update([
            'variants' => array_map(fn(VariantData $v) => $v->toArray(), $variants),
        ]);
    }
}
