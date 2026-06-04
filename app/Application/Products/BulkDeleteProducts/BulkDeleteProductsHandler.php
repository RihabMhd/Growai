<?php

namespace App\Application\Products\BulkDeleteProducts;

use App\Domain\Products\Contracts\ProductRepositoryInterface;
use App\Domain\Products\Exceptions\ProductShopMismatchException;

final class BulkDeleteProductsHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    /**
     * Deletes products by ID, scoped strictly to the given shop.
     *
     * Ownership is enforced at the repository query level — products belonging
     * to a different shop are silently excluded from deletion, not errored.
     * Returns the count of actually deleted records.
     *
     * If strict ownership validation is required (reject entire batch on mismatch),
     * use findByIdsAndShop first and compare counts before deleting.
     */
    public function handle(BulkDeleteProductsCommand $command): int
    {
        if (empty($command->ids)) {
            return 0;
        }

        return $this->repository->bulkDeleteByShop($command->ids, $command->shopId);
    }
}