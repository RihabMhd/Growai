<?php

namespace App\Application\Products\GetProduct;

use App\Domain\Products\Contracts\ProductRepositoryInterface;
use App\Domain\Products\Models\Product;

final class GetProductHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    public function handle(GetProductQuery $query): Product
    {
        return $this->repository->findByIdAndShop($query->productId, $query->shopId);
    }
}