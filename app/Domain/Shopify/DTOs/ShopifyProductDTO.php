<?php

namespace App\Domain\Shopify\DTOs;

final readonly class ShopifyProductDTO
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $handle,
        public ?string $vendor,
        public ?string $productType,
        public ?string $description,
        public ?string $image,
        public array $images,
        public array $variants,
        public string $status,
    ) {}
}