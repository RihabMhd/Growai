<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Products\Models\Product;

// resolve price from first variant, then cost, then fallback to zero
class ProductPriceResolver
{
    public function resolve(Product $product): float
    {
        if (! empty($product->variants)) {
            $variants = is_array($product->variants)
                ? $product->variants
                : json_decode($product->variants, true);

            $firstVariantPrice = $variants[0]['price'] ?? null;

            if ($firstVariantPrice !== null) {
                return (float) $firstVariantPrice;
            }
        }

        return (float) ($product->cost ?? 0);
    }
}