<?php

namespace App\Infrastructure\Shopify\Services;

use App\Domain\Products\Models\Product;
use App\Domain\Shopify\Models\Shop;
use App\Domain\Shopify\DTOs\ShopifyProductDTO;
use App\Infrastructure\Shopify\Mappers\ShopifyProductMapper;
use Illuminate\Support\Facades\Log;

final readonly class ShopifyProductImporter
{
    public function __construct(
        private ShopifyProductMapper $mapper
    ) {}

    public function sync(
        Shop $shop,
        array $products
    ): array {

        $imported = 0;
        $updated = 0;

        foreach ($products as $payload) {

            $dto = $this->mapper->toDto(
                $payload
            );

            $exists = Product::where(
                'shop_id',
                $shop->id
            )
                ->where(
                    'external_product_id',
                    $dto->id
                )
                ->exists();

            $this->upsert(
                $shop,
                $dto
            );

            if ($exists) {
                $updated++;
            } else {
                $imported++;
            }
        }

        Log::info(
            'Shopify products synchronized',
            [
                'shop_id' => $shop->id,
                'imported' => $imported,
                'updated' => $updated,
                'total' => count($products),
            ]
        );

        return [
            'success' => true,
            'imported' => $imported,
            'updated' => $updated,
            'total' => count($products),
        ];
    }

    public function upsert(Shop $shop, ShopifyProductDTO $dto): Product
    {

        $existing = Product::where('shop_id', $shop->id)
            ->where('external_product_id', $dto->id)
            ->first();

        // for existing products, preserve local stock during re-sync
        $variants = $existing
            ? $this->mergeVariants($existing->variants ?? [], $dto->variants)
            : $dto->variants;

        return Product::updateOrCreate(
            [
                'shop_id'             => $shop->id,
                'external_product_id' => $dto->id,
            ],
            [
                'title'        => $dto->title,
                'vendor'       => $dto->vendor,
                'product_type' => $dto->productType,
                'handle'       => $dto->handle,
                'status'       => $dto->status,
                'image'        => $dto->image,
                'images'       => $dto->images,
                'description'  => $dto->description,
                'variants'     => $variants,
                'source_type'  => 'shopify',
            ]
        );
    }

    public function markDeleted(
        Shop $shop,
        string $productId
    ): void {

        Product::where(
            'shop_id',
            $shop->id
        )
            ->where(
                'external_product_id',
                $productId
            )
            ->update([
                'status' => 'deleted',
            ]);
    }

    public function updateFromWebhook(
        Shop $shop,
        array $payload
    ): ?Product {

        $dto = $this->mapper->toDto($payload);

        return Product::where('shop_id', $shop->id)
            ->where('external_product_id', $dto->id)
            ->tap(function ($query) use ($dto, $shop) {

                // build variants preserving local stock, only update shopify metadata fields
                $existing = Product::where('shop_id', $shop->id)
                    ->where('external_product_id', $dto->id)
                    ->first();

                $mergedVariants = $this->mergeVariants(
                    $existing?->variants ?? [],
                    $dto->variants
                );

                $query->update([
                    'title'        => $dto->title,
                    'vendor'       => $dto->vendor,
                    'product_type' => $dto->productType,
                    'handle'       => $dto->handle,
                    'status'       => $dto->status,
                    'image'        => $dto->image,
                    'images'       => $dto->images,
                    'description'  => $dto->description,
                    'variants'     => $mergedVariants,
                ]);
            })
            ->first();
    }

    // merge incoming shopify variant metadata with locally-held stock values
    private function mergeVariants(array $storedVariants, array $incomingVariants): array
    {
        // index stored variants by external variant id
        $storedByVariantId = collect($storedVariants)
            ->filter(fn($v) => !empty($v['external_variant_id']))
            ->keyBy('external_variant_id');

        return array_map(function (array $incoming) use ($storedByVariantId): array {
            $variantId = (string) ($incoming['external_variant_id'] ?? '');
            $stored    = $storedByVariantId->get($variantId);

            return [
                // shopify metadata, always take from webhook
                'external_variant_id'         => $incoming['external_variant_id'] ?? null,
                'external_inventory_item_id'  => $incoming['external_inventory_item_id'] ?? null,
                'shopify_variant_id'          => $incoming['shopify_variant_id'] ?? null,
                'title'                       => $incoming['title'] ?? null,
                'sku'                         => $incoming['sku'] ?? null,
                'price'                       => $incoming['price'] ?? 0,
                'compare_at_price'            => $incoming['compare_at_price'] ?? null,
                'option1'                     => $incoming['option1'] ?? null,
                'option2'                     => $incoming['option2'] ?? null,
                'option3'                     => $incoming['option3'] ?? null,
                'cost'                        => $stored['cost'] ?? null,
                // stock is authoritative, never overwrite from webhook
                'stock'                       => $stored['stock'] ?? $incoming['stock'] ?? 0,
            ];
        }, $incomingVariants);
    }
}
