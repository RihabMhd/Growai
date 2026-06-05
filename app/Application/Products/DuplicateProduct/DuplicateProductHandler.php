<?php

namespace App\Application\Products\DuplicateProduct;

use App\Domain\Products\Contracts\ProductRepositoryInterface;
use App\Domain\Products\DTOs\ProductData;
use App\Domain\Products\Models\Product;
use Illuminate\Support\Str;

final class DuplicateProductHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    /**
     * Duplicates a product within the same shop.
     * - shop_id sourced from the original product — never from command input.
     * - status forced to 'draft'.
     * - source_type forced to 'manual' — the copy is not a Shopify product.
     * - handle uniquified within the shop.
     * - title suffixed with ' (Copy)'.
     */
    public function handle(DuplicateProductCommand $command): Product
    {
        $source = $this->repository->findByIdAndShop($command->productId, $command->shopId);

        $baseHandle = Str::slug($source->handle . '-copy');
        $handle     = $baseHandle;
        $counter    = 1;

        while ($this->repository->handleExistsInShop($handle, $source->shop_id)) {
            $handle = $baseHandle . '-' . $counter;
            $counter++;
        }

        $data = new ProductData(
            shopId:      $source->shop_id,
            title:       $source->title . ' (Copy)',
            status:      'draft',
            sourceType:  'manual',
            vendor:      $source->vendor,
            productType: $source->product_type,
            handle:      $handle,
            description: $source->description,
            image:       $source->image,
            cost:        $source->cost,
            tags:        $source->tags ?? [],
            variants:    $source->variants ?? [],
            images:      $source->images ?? [],
        );

        return $this->repository->create($data);
    }
}