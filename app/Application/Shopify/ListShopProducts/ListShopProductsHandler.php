<?php

namespace App\Application\Shopify\ListShopProducts;

use App\Domain\Products\Models\Product;

final readonly class ListShopProductsHandler
{
    public function handle(
        ListShopProductsQuery $query
    )
    {
        $products = Product::query()
            ->where('shop_id', $query->shopId)
            ->where('status', 'active');

        if ($query->search) {
            $products->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query->search}%")
                  ->orWhere('vendor', 'like', "%{$query->search}%");
            });
        }

        return $products
            ->latest()
            ->paginate($query->perPage);
    }
}