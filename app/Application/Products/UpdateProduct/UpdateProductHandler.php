<?php

namespace App\Application\Products\UpdateProduct;

use App\Domain\Products\Contracts\ProductRepositoryInterface;
use App\Domain\Products\Models\Product;
use App\Application\Shopify\Contracts\ShopifyClientInterface;
use App\Domain\Products\DTOs\ProductData;
use App\Domain\Products\DTOs\VariantData;
use App\Domain\Shopify\Exceptions\ShopifyApiException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class UpdateProductHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
        private readonly ShopifyClientInterface     $shopifyClient,
    ) {}

    public function handle(UpdateProductCommand $command): Product
    {
        $product = $this->repository->findByIdAndShop($command->productId, $command->shopId);

        if ($product->source_type === 'shopify') {
            return $this->handleShopifyProduct($product, $command);
        }

        return $this->handleManualProduct($product, $command);
    }

    private function syncInventory(Product $product, ProductData $data): void
    {
        if (empty($data->variants)) {
            return;
        }

        $shop       = $product->shop;
        $locationId = $shop->shopify_location_id;

        if (empty($locationId)) {
            Log::warning('Shopify inventory sync skipped: no location id', [
                'shop_id' => $shop->id,
            ]);
            return;
        }

        $storedByVariantId = collect($product->variants ?? [])
            ->filter(fn($v) => !empty($v['external_variant_id']))
            ->keyBy('external_variant_id');

        foreach ($data->variants as $incomingVariant) {
            $variantId = $incomingVariant instanceof VariantData
                ? $incomingVariant->externalVariantId
                : ($incomingVariant['external_variant_id'] ?? null);

            $newStock = $incomingVariant instanceof VariantData
                ? $incomingVariant->stock
                : ($incomingVariant['stock'] ?? null);

            if (!$variantId || $newStock === null) {
                continue;
            }

            $stored = $storedByVariantId->get((string) $variantId);

            if (!$stored) {
                Log::warning('syncInventory: no stored variant found', [
                    'external_variant_id' => $variantId,
                    'product_id'          => $product->id,
                ]);
                continue;
            }

            $inventoryItemId = $stored['external_inventory_item_id'] ?? null;

            if (!$inventoryItemId) {
                Log::warning('syncInventory: missing external_inventory_item_id', [
                    'external_variant_id' => $variantId,
                    'product_id'          => $product->id,
                ]);
                continue;
            }

            $this->shopifyClient->setInventoryLevel(
                $shop,
                (string) $inventoryItemId,
                (string) $locationId,
                (int) $newStock
            );
        }
    }

    private function handleShopifyProduct(Product $product, UpdateProductCommand $command): Product
    {
        if (empty($product->external_product_id)) {
            return $this->repository->update($product, $command->data);
        }

        $shop    = $product->shop;
        $payload = $this->buildShopifyPayload($command->data, $product);

        $this->shopifyClient->updateProduct(
            $shop,
            (string) $product->external_product_id,
            $payload
        );

        try {
            $this->syncInventory($product, $command->data);
        } catch (ShopifyApiException $e) {
            Log::error('Inventory sync failed — product update succeeded', [
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
            ]);
        }

        return $this->repository->update($product, $command->data);
    }

    private function buildShopifyPayload(ProductData $data, Product $product): array
    {
        $payload = [];

        if ($data->title !== null)       $payload['title']        = $data->title;
        if ($data->description !== null) $payload['body_html']    = $data->description;
        if ($data->vendor !== null)      $payload['vendor']       = $data->vendor;
        if ($data->productType !== null) $payload['product_type'] = $data->productType;
        if ($data->status !== null)      $payload['status']       = $data->status;
        if ($data->tags)                 $payload['tags']         = implode(', ', $data->tags);

        if ($data->images !== null) {
            $payload['images'] = array_map(
                fn($img) => is_array($img) ? $img : ['src' => $img],
                $data->images
            );
        }

        if (!empty($data->variants)) {
            $payload['variants'] = array_map(function ($v) {
                $v       = $v instanceof VariantData ? $v->toArray() : (array) $v;
                $variant = [];
                if (!empty($v['external_variant_id'])) $variant['id']               = $v['external_variant_id'];
                if (isset($v['price']))                 $variant['price']            = (string) $v['price'];
                if (isset($v['compare_at_price']))      $variant['compare_at_price'] = (string) $v['compare_at_price'];
                if (!empty($v['sku']))                  $variant['sku']              = $v['sku'];
                if (!empty($v['title']))                $variant['title']            = $v['title'];
                return $variant;
            }, $data->variants);
        }

        return $payload;
    }

    private function handleManualProduct(Product $product, UpdateProductCommand $command): Product
    {
        $data = $command->data;

        if ($data->handle !== null && $data->handle !== $product->handle) {
            $data = $this->resolveUniqueHandle($data, $product->id);
        }

        return $this->repository->update($product, $data);
    }

    private function resolveUniqueHandle(ProductData $data, int $excludeId): ProductData
    {
        $base    = Str::slug($data->handle);
        $handle  = $base;
        $counter = 1;

        while ($this->repository->handleExistsInShop($handle, $data->shopId, $excludeId)) {
            $handle = $base . '-' . $counter;
            $counter++;
        }

        return new ProductData(
            shopId:      $data->shopId,
            title:       $data->title,
            status:      $data->status,
            sourceType:  $data->sourceType,
            vendor:      $data->vendor,
            productType: $data->productType,
            handle:      $handle,
            description: $data->description,
            image:       $data->image,
            cost:        $data->cost,
            tags:        $data->tags,
            variants:    $data->variants,
            images:      $data->images,
        );
    }
}