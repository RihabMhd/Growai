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

    // force manual source type because copies are not shopify products
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