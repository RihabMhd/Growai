<?php

namespace App\Application\Products\BulkDeleteProducts;

use App\Domain\Products\Contracts\ProductRepositoryInterface;
use App\Domain\Products\Exceptions\ProductShopMismatchException;

final class BulkDeleteProductsHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    // products belonging to another shop are silently ignored
    public function handle(BulkDeleteProductsCommand $command): int
    {
        if (empty($command->ids)) {
            return 0;
        }

        return $this->repository->bulkDeleteByShop($command->ids, $command->shopId);
    }
}