<?php

namespace App\Domain\Products\Contracts;

use App\Domain\Products\DTOs\ProductData;
use App\Domain\Products\DTOs\ProductFilterData;
use App\Domain\Products\DTOs\ProductSummaryData;
use App\Domain\Products\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    /**
     * Paginated product list scoped to a shop.
     */
    public function findAllByShop(int $shopId, ProductFilterData $filters): LengthAwarePaginator;

    /**
     * Find a product by primary key regardless of shop.
     * Use only in internal/webhook contexts where shop is already validated upstream.
     */
    public function findById(int $id): ?Product;

    /**
     * Find a product by primary key, asserting shop ownership.
     * Throws ProductNotFoundException if not found.
     * Throws ProductShopMismatchException if product exists but belongs to a different shop.
     */
    public function findByIdAndShop(int $id, int $shopId): Product;

    /**
     * Find a product by handle regardless of shop.
     */
    public function findByHandle(string $handle): ?Product;

    /**
     * Find a product by handle scoped to a shop.
     */
    public function findByHandleAndShop(string $handle, int $shopId): ?Product;

    /**
     * Load multiple products by IDs, asserting all belong to the given shop.
     * Returns only products that match both ID and shop_id.
     * Callers must compare count to detect ownership violations.
     */
    public function findByIdsAndShop(array $ids, int $shopId): Collection;

    /**
     * Paginated products filtered by a single tag, scoped to a shop.
     */
    public function findByTagAndShop(string $tag, int $shopId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Full-text search across title, handle, vendor, product_type, tags — scoped to shop.
     */
    public function searchByShop(string $term, int $shopId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Aggregated summary counts and values for a shop.
     */
    public function getSummaryByShop(int $shopId): ProductSummaryData;

    /**
     * Check whether a handle already exists, optionally excluding a product by ID.
     */
    public function handleExistsInShop(string $handle, int $shopId, ?int $excludeId = null): bool;

    /**
     * Persist a new or existing product.
     */
    public function save(Product $product): Product;

    /**
     * Persist product from a data transfer object (used in create flow).
     */
    public function create(ProductData $data): Product;

    /**
     * Apply a data transfer object to an existing product and persist.
     */
    public function update(Product $product, ProductData $data): Product;

    /**
     * Delete a single product.
     */
    public function delete(Product $product): bool;

    /**
     * Delete multiple products by ID, scoped to shop.
     * Returns count of deleted records.
     */
    public function bulkDeleteByShop(array $ids, int $shopId): int;

    /**
     * Update status on multiple products by ID, scoped to shop.
     * Returns count of updated records.
     */
    public function bulkUpdateStatusByShop(array $ids, string $status, int $shopId): int;
}