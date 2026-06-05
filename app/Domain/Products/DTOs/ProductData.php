<?php

namespace App\Domain\Products\DTOs;

use App\Domain\Products\DTOs\VariantData;

final class ProductData
{
    /**
     * @param VariantData[] $variants
     * @param string[]      $tags
     */
    public function __construct(
        public readonly int     $shopId,
        public readonly string  $title,
        public readonly ?string $status,
        public readonly ?string $sourceType,
        public readonly ?string $vendor,
        public readonly ?string $productType,
        public readonly ?string $handle,
        public readonly ?string $description,
        public readonly ?string $image,
        public readonly ?float  $cost,
        public readonly array   $tags,
        public readonly array   $variants,
        public readonly ?array  $images,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            shopId: (int) $data['shop_id'],
            title: $data['title'],
            status: $data['status']       ?? null,
            sourceType: $data['source_type']  ?? null,                                          
            vendor: $data['vendor']       ?? null,
            productType: $data['product_type'] ?? null,
            handle: isset($data['handle']) && $data['handle'] !== '' ? $data['handle'] : null,
            description: $data['description']  ?? null,
            image: $data['image']        ?? null,
            cost: isset($data['cost'])   ? (float) $data['cost'] : null,
            tags: self::normalizeTags($data),
            variants: self::normalizeVariants($data['variants'] ?? []),
            images: array_key_exists('images', $data) ? $data['images'] : null,            
        );
    }

    /**
     * Normalize tags from either a comma-delimited string, tags_string, or array.
     *
     * @return string[]
     */
    private static function normalizeTags(array $data): array
    {
        if (isset($data['tags_string']) && is_string($data['tags_string'])) {
            return array_values(array_filter(array_map('trim', explode(',', $data['tags_string']))));
        }

        if (isset($data['tags'])) {
            if (is_string($data['tags'])) {
                return array_values(array_filter(array_map('trim', explode(',', $data['tags']))));
            }

            if (is_array($data['tags'])) {
                return array_values(array_filter(array_map('trim', $data['tags'])));
            }
        }

        return [];
    }

    /**
     * Normalize variants — decode JSON string if needed, cast numeric fields.
     *
     * @return VariantData[]
     */
    private static function normalizeVariants(mixed $variants): array
    {
        if (is_string($variants)) {
            $variants = json_decode($variants, true) ?? [];
        }

        if (!is_array($variants)) {
            return [];
        }

        return array_map(fn(array $v) => VariantData::fromArray($v), $variants);
    }

    public function toArray(): array
    {
        return [
            'shop_id'      => $this->shopId,
            'title'        => $this->title,
            'status'       => $this->status,
            'source_type'  => $this->sourceType,
            'vendor'       => $this->vendor,
            'product_type' => $this->productType,
            'handle'       => $this->handle,
            'description'  => $this->description,
            'image'        => $this->image,
            'cost'         => $this->cost,
            'tags'         => $this->tags,
            'variants'     => array_map(fn(VariantData $v) => $v->toArray(), $this->variants),
            'images'       => $this->images,
        ];
    }
}
