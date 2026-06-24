<?php

namespace App\Application\Products\SyncProductFromWebhook;

use App\Domain\Products\Contracts\ProductRepositoryInterface;
use App\Domain\Products\DTOs\ProductData;
use App\Domain\Products\DTOs\VariantData;
use App\Domain\Products\Models\Product;

final class SyncProductFromWebhookHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    // write product data when shopify fires the webhook
    public function handle(SyncProductFromWebhookCommand $command): ?Product
    {
        return match ($command->event) {
            'products/create', 'products/update' => $this->upsert($command),
            'products/delete'                    => $this->delete($command),
            default => null,
        };
    }

    private function upsert(SyncProductFromWebhookCommand $command): Product
    {
        $payload = $command->shopifyPayload;

        $existing = Product::where('shopify_product_id', $payload['id'])
            ->where('shop_id', $command->shopId)
            ->first();

        $data = $this->mapPayloadToProductData($payload, $command->shopId);

        if ($existing) {
            return $this->repository->update($existing, $data);
        }

        return $this->repository->create($data);
    }

    private function delete(SyncProductFromWebhookCommand $command): null
    {
        $product = Product::where('shopify_product_id', $command->shopifyPayload['id'])
            ->where('shop_id', $command->shopId)
            ->first();

        if ($product) {
            $this->repository->delete($product);
        }

        return null;
    }

    private function mapPayloadToProductData(array $payload, int $shopId): ProductData
    {
        $variants = array_map(function (array $v): VariantData {
            return VariantData::fromArray([
                'price'             => $v['price'] ?? 0,
                'stock'             => $v['inventory_quantity'] ?? 0,
                'compare_at_price'  => $v['compare_at_price'] ?? null,
                'cost'              => null, // cost is not provided in the webhook
                'sku'               => $v['sku'] ?? null,
                'title'             => $v['title'] ?? null,
                'option1'           => $v['option1'] ?? null,
                'option2'           => $v['option2'] ?? null,
                'option3'           => $v['option3'] ?? null,
                'shopify_variant_id'=> $v['id'] ?? null,
            ]);
        }, $payload['variants'] ?? []);

        $tags = array_values(
            array_filter(
                array_map('trim', explode(',', $payload['tags'] ?? ''))
            )
        );

        $images = array_map(fn(array $img) => $img['src'] ?? null, $payload['images'] ?? []);
        $images = array_values(array_filter($images));

        return new ProductData(
            shopId:      $shopId,
            title:       $payload['title'],
            status:      $this->mapShopifyStatus($payload['status'] ?? 'active'),
            sourceType:  'shopify',
            vendor:      $payload['vendor'] ?? null,
            productType: $payload['product_type'] ?? null,
            handle:      $payload['handle'] ?? null,
            description: $payload['body_html'] ?? null,
            image:       $payload['image']['src'] ?? null,
            cost:        null,
            tags:        $tags,
            variants:    $variants,
            images:      $images,
        );
    }

    private function mapShopifyStatus(string $shopifyStatus): string
    {
        return match ($shopifyStatus) {
            'active'   => 'active',
            'archived' => 'archived',
            default    => 'draft',
        };
    }
}