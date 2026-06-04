<?php

namespace App\Application\Products\BulkUpdateStatus;

final class BulkUpdateStatusCommand
{
    /**
     * @param int[] $ids
     */
    public function __construct(
        public readonly array  $ids,
        public readonly int    $shopId,
        public readonly string $status,
    ) {}
}