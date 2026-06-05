<?php

namespace App\Application\Products\SearchProducts;

final class SearchProductsQuery
{
    public function __construct(
        public readonly int    $shopId,
        public readonly string $term,
        public readonly int    $perPage = 15,
    ) {}
}