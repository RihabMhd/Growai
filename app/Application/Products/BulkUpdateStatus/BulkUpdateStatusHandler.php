<?php

namespace App\Application\Products\BulkUpdateStatus;

use App\Domain\Products\Contracts\ProductRepositoryInterface;

final class BulkUpdateStatusHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    // products belonging to another shop are silently ignored
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