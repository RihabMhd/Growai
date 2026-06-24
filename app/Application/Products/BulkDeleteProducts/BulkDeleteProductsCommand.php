<?php

namespace App\Application\Products\BulkDeleteProducts;

final class BulkDeleteProductsCommand
{
    public function __construct(
        public readonly array $ids,
        public readonly int   $shopId,
    ) {}
}