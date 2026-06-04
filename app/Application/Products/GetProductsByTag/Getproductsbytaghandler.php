<?php

namespace App\Application\Products\GetProductsByTag;

use App\Domain\Products\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class GetProductsByTagHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    public function handle(GetProductsByTagQuery $query): LengthAwarePaginator
    {
        return $this->repository->findByTagAndShop($query->tag, $query->shopId, $query->perPage);
    }
}