<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Products\Models\Product;

/**
 * Resolves the correct unit price for a product.
 *
 * Priority:
 *   1. First variant price (if variants exist and have a price)
 *   2. Product cost
 *   3. 0.00 as a safe fallback
 *
 * This logic was duplicated in store(), update(), and bulkUpdateStatus().
 * Single place to change pricing resolution rules going forward.
 *
 * Usage:
 *   $price = (new ProductPriceResolver)->resolve($product);
 */
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