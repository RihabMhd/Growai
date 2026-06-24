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

    public function findAllByShop(int $shopId, ProductFilterData $filters): LengthAwarePaginator;

    // shop validation must happen upstream when using this
    public function findById(int $id): ?Product;


    public function findByIdAndShop(int $id, int $shopId): Product;


    public function findByHandle(string $handle): ?Product;


    public function findByHandleAndShop(string $handle, int $shopId): ?Product;

    // compare result count to check ownership violations
    public function findByIdsAndShop(array $ids, int $shopId): Collection;


    public function findByTagAndShop(string $tag, int $shopId, int $perPage = 15): LengthAwarePaginator;


    public function searchByShop(string $term, int $shopId, int $perPage = 15): LengthAwarePaginator;


    public function getSummaryByShop(int $shopId): ProductSummaryData;


    public function handleExistsInShop(string $handle, int $shopId, ?int $excludeId = null): bool;


    public function save(Product $product): Product;


    public function create(ProductData $data): Product;


    public function update(Product $product, ProductData $data): Product;


    public function delete(Product $product): bool;


    public function bulkDeleteByShop(array $ids, int $shopId): int;


    public function bulkUpdateStatusByShop(array $ids, string $status, int $shopId): int;
}