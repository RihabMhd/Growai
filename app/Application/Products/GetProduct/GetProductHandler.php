<?php

namespace App\Application\Products\GetProduct;

use App\Domain\Products\Contracts\ProductRepositoryInterface;
use App\Domain\Products\Models\Product;

final class GetProductHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    /**
     * Loads a product by ID, asserting it belongs to the given shop.
     * Throws ProductNotFoundException or ProductShopMismatchException on failure.
     */
    public function handle(GetProductQuery $query): Product
    {
        return $this->repository->findByIdAndShop($query->productId, $query->shopId);
    }
}