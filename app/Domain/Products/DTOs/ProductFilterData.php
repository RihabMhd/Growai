<?php

namespace App\Domain\Products\DTOs;

final class ProductFilterData
{
    public function __construct(
        public readonly ?string $status,
        public readonly ?string $sourceType,
        public readonly ?string $vendor,
        public readonly ?string $productType,
        public readonly ?string $search,
        public readonly ?string $tag,
        public readonly ?float  $minPrice,
        public readonly ?string $stockStatus,
        public readonly string  $sortBy,
        public readonly string  $sortOrder,
        public readonly int     $perPage,
    ) {}

    private const ALLOWED_SORT_FIELDS = [
        'created_at', 'updated_at', 'title',
        'price', 'stock', 'status', 'vendor',
    ];

    private const ALLOWED_SORT_ORDERS = ['asc', 'desc'];

    private const ALLOWED_STOCK_STATUSES = ['in_stock', 'out_of_stock', 'low_stock'];

    private const ALLOWED_STATUSES = ['active', 'draft', 'archived'];

    private const ALLOWED_SOURCE_TYPES = ['manual', 'shopify'];

    public static function fromArray(array $data): self
    {
        $sortBy    = in_array($data['sort_by'] ?? null, self::ALLOWED_SORT_FIELDS, true)
                         ? $data['sort_by']
                         : 'created_at';

        $sortOrder = in_array($data['sort_order'] ?? null, self::ALLOWED_SORT_ORDERS, true)
                         ? $data['sort_order']
                         : 'desc';

        $status      = in_array($data['status'] ?? null, self::ALLOWED_STATUSES, true)
                           ? $data['status']
                           : null;

        $sourceType  = in_array($data['source_type'] ?? null, self::ALLOWED_SOURCE_TYPES, true)
                           ? $data['source_type']
                           : null;

        $stockStatus = in_array($data['stock_status'] ?? null, self::ALLOWED_STOCK_STATUSES, true)
                           ? $data['stock_status']
                           : null;

        $perPage = isset($data['per_page'])
                       ? max(1, min(100, (int) $data['per_page']))
                       : 15;

        return new self(
            status:      $status,
            sourceType:  $sourceType,
            vendor:      isset($data['vendor'])       && $data['vendor'] !== ''       ? $data['vendor']       : null,
            productType: isset($data['product_type']) && $data['product_type'] !== '' ? $data['product_type'] : null,
            search:      isset($data['search'])       && $data['search'] !== ''       ? $data['search']       : null,
            tag:         isset($data['tag'])           && $data['tag'] !== ''          ? $data['tag']          : null,
            minPrice:    isset($data['min_price'])     ? (float) $data['min_price']    : null,
            stockStatus: $stockStatus,
            sortBy:      $sortBy,
            sortOrder:   $sortOrder,
            perPage:     $perPage,
        );
    }
}