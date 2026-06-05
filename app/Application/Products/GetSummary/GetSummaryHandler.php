<?php

namespace App\Application\Products\GetSummary;

use App\Domain\Products\Contracts\ProductRepositoryInterface;
use App\Domain\Products\DTOs\ProductSummaryData;

final class GetSummaryHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    public function handle(GetSummaryQuery $query): ProductSummaryData
    {
        return $this->repository->getSummaryByShop($query->shopId);
    }
}