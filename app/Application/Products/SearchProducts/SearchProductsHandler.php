<?php

namespace App\Application\Products\SearchProducts;

use App\Domain\Products\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class SearchProductsHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    public function handle(SearchProductsQuery $query): LengthAwarePaginator
    {
        return $this->repository->searchByShop($query->term, $query->shopId, $query->perPage);
    }
}