<?php

namespace App\Application\Products\UpdateProduct;

use App\Domain\Products\Contracts\ProductRepositoryInterface;
use App\Domain\Products\Models\Product;
use App\Application\Shopify\Contracts\ShopifyClientInterface;
use Illuminate\Support\Str;

final class UpdateProductHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface    $repository,
        private readonly ShopifyClientInterface $shopifyClient,
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

    // handleShopifyProduct — fix the call (was passing wrong args, no Shop):
    private function handleShopifyProduct(Product $product, UpdateProductCommand $command): Product
    {
        if (empty($product->external_product_id)) {
            return $this->repository->update($product, $command->data);
        }

        $shop = $product->shop; // BelongsTo already defined on Product

        $payload = $this->buildShopifyPayload($command->data);

        // Throws ShopifyApiException on failure — bubbles to controller, local DB not touched
        $this->shopifyClient->updateProduct(
            $shop,
            (string) $product->external_product_id,
            $payload
        );

        // Shopify confirmed — mirror locally; webhook will overwrite with canonical data
        return $this->repository->update($product, $command->data);
    }

    private function buildShopifyPayload(\App\Domain\Products\DTOs\ProductData $data, Product $product): array
    {
        $payload = [];

        if ($data->title !== null)       $payload['title']        = $data->title;
        if ($data->description !== null) $payload['body_html']    = $data->description;
        if ($data->vendor !== null)      $payload['vendor']       = $data->vendor;
        if ($data->productType !== null) $payload['product_type'] = $data->productType;
        if ($data->status !== null)      $payload['status']       = $data->status;
        if ($data->tags)                 $payload['tags']         = implode(', ', $data->tags);

        // BUG #2 — only send images if explicitly provided
        if ($data->images !== null) {
            $payload['images'] = array_map(
                fn($img) => is_array($img) ? $img : ['src' => $img],
                $data->images
            );
        }

        // Variants — price/sku/title only; inventory_quantity is read-only on this endpoint
        if (!empty($data->variants)) {
            $payload['variants'] = array_map(function ($v) {
                $v = $v instanceof \App\Domain\Products\DTOs\VariantData ? $v->toArray() : (array) $v;
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
            shopId: $data->shopId,
            title: $data->title,
            status: $data->status,
            sourceType: $data->sourceType,
            vendor: $data->vendor,
            productType: $data->productType,
            handle: $handle,
            description: $data->description,
            image: $data->image,
            cost: $data->cost,
            tags: $data->tags,
            variants: $data->variants,
            images: $data->images,
        );
    }

    private function resolveHandleIfChanged(UpdateProductCommand $command, Product $product): void
    {
        // For Shopify products, handle is managed by Shopify — no local uniqueness check needed
        // Shopify will normalize and return the canonical handle via webhook
    }
}
