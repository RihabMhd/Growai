<?php

namespace App\Application\Products\GetProductsByTag;

final class GetProductsByTagQuery
{
    public function __construct(
        public readonly int    $shopId,
        public readonly string $tag,
        public readonly int    $perPage = 15,
    ) {}
}