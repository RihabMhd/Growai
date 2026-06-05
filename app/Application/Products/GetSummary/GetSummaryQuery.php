<?php

namespace App\Application\Products\GetSummary;

final class GetSummaryQuery
{
    public function __construct(
        public readonly int $shopId,
    ) {}
}