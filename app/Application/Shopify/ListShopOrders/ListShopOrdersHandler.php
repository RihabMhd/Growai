<?php

namespace App\Application\Shopify\ListShopOrders;

use App\Domain\Orders\Models\Order;

final readonly class ListShopOrdersHandler
{
    public function handle(
        ListShopOrdersQuery $query
    )
    {
        $orders = Order::with('items')
            ->where('shop_id', $query->shopId);

        if ($query->status) {
            $orders->where(
                'status',
                $query->status
            );
        }

        if ($query->search) {
            $orders->where(function ($q) use ($query) {
                $q->where('order_number', 'like', "%{$query->search}%")
                  ->orWhere('customer_email', 'like', "%{$query->search}%")
                  ->orWhere('customer_name', 'like', "%{$query->search}%");
            });
        }

        return $orders
            ->latest('shopify_created_at')
            ->paginate($query->perPage);
    }
}