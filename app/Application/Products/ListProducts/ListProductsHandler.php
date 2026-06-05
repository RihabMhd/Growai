<?php

namespace App\Application\Products\ListProducts;

use App\Domain\Products\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListProductsHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    public function handle(ListProductsQuery $query): LengthAwarePaginator
    {
        return $this->repository->findAllByShop($query->shopId, $query->filters);
    }
}