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

    public function upsert(
        Shop $shop,
        ShopifyProductDTO $dto
    ): Product {
        Log::info('DTO_VARIANTS', [
            'product_id' => $dto->id,
            'variants' => $dto->variants,
        ]);

        return Product::updateOrCreate(
            [
                'shop_id' => $shop->id,
                'external_product_id' => $dto->id,
            ],
            [
                'title' => $dto->title,
                'vendor' => $dto->vendor,
                'product_type' => $dto->productType,
                'handle' => $dto->handle,
                'status' => $dto->status,
                'image' => $dto->image,
                'images' => $dto->images,
                'description' => $dto->description,
                'variants' => $dto->variants,
                'source_type' => 'shopify',
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

        $dto = $this->mapper->toDto(
            $payload
        );

        return Product::where(
            'shop_id',
            $shop->id
        )
            ->where(
                'external_product_id',
                $dto->id
            )
            ->tap(function ($query) use ($dto) {

                $query->update([
                    'title' => $dto->title,
                    'vendor' => $dto->vendor,
                    'product_type' => $dto->productType,
                    'handle' => $dto->handle,
                    'status' => $dto->status,
                    'image' => $dto->image,
                    'images' => $dto->images,
                    'description' => $dto->description,
                    'variants' => $dto->variants,
                ]);
            })
            ->first();
    }
}
