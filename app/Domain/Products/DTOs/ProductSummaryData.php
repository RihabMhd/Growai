<?php

namespace App\Domain\Products\DTOs;

final class ProductSummaryData
{
    public function __construct(
        public readonly int   $total,
        public readonly int   $active,
        public readonly int   $draft,
        public readonly int   $archived,
        public readonly int   $manual,
        public readonly int   $shopify,
        public readonly int   $outOfStock,
        public readonly int   $lowStock,
        public readonly float $totalValue,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            total:      (int)   ($data['total']       ?? 0),
            active:     (int)   ($data['active']      ?? 0),
            draft:      (int)   ($data['draft']       ?? 0),
            archived:   (int)   ($data['archived']    ?? 0),
            manual:     (int)   ($data['manual']      ?? 0),
            shopify:    (int)   ($data['shopify']     ?? 0),
            outOfStock: (int)   ($data['out_of_stock'] ?? 0),
            lowStock:   (int)   ($data['low_stock']   ?? 0),
            totalValue: (float) ($data['total_value'] ?? 0.0),
        );
    }

    public function toArray(): array
    {
        return [
            'total'        => $this->total,
            'active'       => $this->active,
            'draft'        => $this->draft,
            'archived'     => $this->archived,
            'manual'       => $this->manual,
            'shopify'      => $this->shopify,
            'out_of_stock' => $this->outOfStock,
            'low_stock'    => $this->lowStock,
            'total_value'  => $this->totalValue,
        ];
    }
}