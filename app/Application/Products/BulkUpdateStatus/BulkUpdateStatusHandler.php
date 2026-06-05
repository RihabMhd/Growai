<?php

namespace App\Application\Products\BulkUpdateStatus;

use App\Domain\Products\Contracts\ProductRepositoryInterface;

final class BulkUpdateStatusHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    /**
     * Updates status on products scoped strictly to the given shop.
     * Products belonging to other shops are excluded at query level.
     * Returns count of updated records.
     */
    public function handle(BulkUpdateStatusCommand $command): int
    {
        if (empty($command->ids)) {
            return 0;
        }

        return $this->repository->bulkUpdateStatusByShop(
            $command->ids,
            $command->status,
            $command->shopId,
        );
    }
}