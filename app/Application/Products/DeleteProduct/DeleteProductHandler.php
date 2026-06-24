<?php

namespace App\Application\Products\DeleteProduct;

use App\Domain\Products\Contracts\ProductRepositoryInterface;

final class DeleteProductHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    public function handle(DeleteProductCommand $command): bool
    {
        $product = $this->repository->findByIdAndShop($command->productId, $command->shopId);

        return $this->repository->delete($product);
    }
}