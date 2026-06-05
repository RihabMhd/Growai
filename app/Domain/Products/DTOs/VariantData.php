<?php

namespace App\Domain\Products\DTOs;

final class VariantData
{
    public function __construct(
        public readonly float   $price,
        public readonly int     $stock,
        public readonly ?float  $compareAtPrice,
        public readonly ?float  $cost,
        public readonly ?string $sku,
        public readonly ?string $title,
        public readonly ?string $option1,
        public readonly ?string $option2,
        public readonly ?string $option3,
        public readonly ?int    $shopifyVariantId,
        public readonly ?string $externalVariantId,
        public readonly ?string $externalInventoryItemId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            price: (float) ($data['price'] ?? 0),
            stock: (int)   ($data['stock'] ?? 0),
            compareAtPrice: isset($data['compare_at_price']) ? (float) $data['compare_at_price'] : null,
            cost: isset($data['cost'])             ? (float) $data['cost']             : null,
            sku: $data['sku']              ?? null,
            title: $data['title']            ?? null,
            option1: $data['option1']          ?? null,
            option2: $data['option2']          ?? null,
            option3: $data['option3']          ?? null,
            shopifyVariantId: isset($data['shopify_variant_id']) ? (int) $data['shopify_variant_id']: null,
            externalVariantId: $data['external_variant_id'] ?? null,
            externalInventoryItemId: $data['external_inventory_item_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'price'             => $this->price,
            'stock'             => $this->stock,
            'compare_at_price'  => $this->compareAtPrice,
            'cost'              => $this->cost,
            'sku'               => $this->sku,
            'title'             => $this->title,
            'option1'           => $this->option1,
            'option2'           => $this->option2,
            'option3'           => $this->option3,
            'shopify_variant_id' => $this->shopifyVariantId,
            'external_variant_id' => $this->externalVariantId,
            'external_inventory_item_id' => $this->externalInventoryItemId,
        ];
    }
}
