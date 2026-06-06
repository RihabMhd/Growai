<?php

namespace App\Infrastructure\Shopify\Mappers;

use App\Domain\Shopify\DTOs\ShopifyProductDTO;
use Illuminate\Support\Facades\Log;
final class ShopifyProductMapper
{
    public function toDto(array $product): ShopifyProductDTO
    {
        return new ShopifyProductDTO(
            id: (string) $product['id'],
            title: $product['title'] ?? '',
            handle: $product['handle'] ?? null,
            vendor: $product['vendor'] ?? null,
            productType: $product['product_type'] ?? null,
            description: $product['body_html'] ?? null,
            image: $this->extractImage($product),
            images: $this->extractImages($product),
            variants: $this->extractVariants($product),
            status: match ($product['status'] ?? 'draft') {
                'active' => 'active',
                'draft' => 'draft',
                'archived' => 'archived',
                default => 'draft',
            },
        );
    }

    private function extractVariants(array $product): array
    {
        $variants = [];
        foreach ($product['variants'] ?? [] as $variant) {
            $variants[] = [
                'title'                       => $variant['title'] ?? 'Default',
                'sku'                         => $variant['sku'] ?? null,
                'price'                       => (float) ($variant['price'] ?? 0),
                'stock'                       => (int) ($variant['inventory_quantity'] ?? 0),
                'compare_at_price'            => $variant['compare_at_price'] ?? null,

                // NEW FIELDS
                'external_variant_id'         => (string) ($variant['id'] ?? ''),
                'external_inventory_item_id'  => (string) ($variant['inventory_item_id'] ?? ''),
            ];
        }
        

        return $variants;
    }

    private function extractImage(array $product): ?string
    {
        return $product['image']['src']
            ?? $product['images'][0]['src']
            ?? null;
    }

    private function extractImages(array $product): array
    {
        $images = [];

        foreach ($product['images'] ?? [] as $image) {

            if (!empty($image['src'])) {

                $images[] = [
                    'src' => $image['src'],
                ];
            }
        }

        return $images;
    }
}
