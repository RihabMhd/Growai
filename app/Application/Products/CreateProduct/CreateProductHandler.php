<?php

namespace App\Application\Products\CreateProduct;

use App\Domain\Products\Contracts\ProductRepositoryInterface;
use App\Domain\Products\DTOs\ProductData;
use App\Domain\Products\Models\Product;
use Illuminate\Support\Str;

final class CreateProductHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    public function handle(CreateProductCommand $command): Product
    {
        $data = $this->resolveHandle($command->data);

        return $this->repository->create($data);
    }

    // create a unique handle for the shop
    private function resolveHandle(ProductData $data): ProductData
    {
        $base   = $data->handle ? Str::slug($data->handle) : Str::slug($data->title);
        $handle = $base;
        $counter = 1;

        while ($this->repository->handleExistsInShop($handle, $data->shopId)) {
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