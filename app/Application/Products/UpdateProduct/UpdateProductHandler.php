<?php

namespace App\Application\Products\UpdateProduct;

use App\Domain\Products\Contracts\ProductRepositoryInterface;
use App\Domain\Products\Models\Product;
use App\Domain\Shopify\Contracts\ShopifyProductClientInterface;
use Illuminate\Support\Str;

final class UpdateProductHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface    $repository,
        private readonly ShopifyProductClientInterface $shopifyClient,
    ) {}

    /**
     * For Shopify-sourced products:
     *   - Push update to Shopify API.
     *   - Do NOT write local DB. The incoming webhook is the sole local writer.
     *   - Return the product with data from the command (optimistic — not from Shopify response).
     *
     * For manual products:
     *   - Write directly to local DB.
     *   - No Shopify interaction.
     */
    public function handle(UpdateProductCommand $command): Product
    {
        $product = $this->repository->findByIdAndShop($command->productId, $command->shopId);

        if ($product->source_type === 'shopify') {
            return $this->handleShopifyProduct($product, $command);
        }

        return $this->handleManualProduct($product, $command);
    }

    private function handleShopifyProduct(Product $product, UpdateProductCommand $command): Product
    {
        $this->resolveHandleIfChanged($command, $product);

        // Push to Shopify — local DB updated by webhook on arrival
        $this->shopifyClient->updateProduct($product->shopify_product_id, $command->data);

        // Return product with pending changes overlaid for optimistic frontend response
        $product->fill($command->data->toArray());

        return $product;
    }

    private function handleManualProduct(Product $product, UpdateProductCommand $command): Product
    {
        $data = $command->data;

        if ($data->handle !== null && $data->handle !== $product->handle) {
            $data = $this->resolveUniqueHandle($data, $product->id);
        }

        return $this->repository->update($product, $data);
    }

    /**
     * Uniquify handle within shop, excluding the current product.
     * Returns a new ProductData — readonly DTO.
     */
    private function resolveUniqueHandle(
        \App\Domain\Products\DTOs\ProductData $data,
        int $excludeId
    ): \App\Domain\Products\DTOs\ProductData {
        $base    = Str::slug($data->handle);
        $handle  = $base;
        $counter = 1;

        while ($this->repository->handleExistsInShop($handle, $data->shopId, $excludeId)) {
            $handle = $base . '-' . $counter;
            $counter++;
        }

        return new \App\Domain\Products\DTOs\ProductData(
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

    private function resolveHandleIfChanged(UpdateProductCommand $command, Product $product): void
    {
        // For Shopify products, handle is managed by Shopify — no local uniqueness check needed
        // Shopify will normalize and return the canonical handle via webhook
    }
}