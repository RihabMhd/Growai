<?php

namespace App\Domain\Orders\Services;

use App\Domain\Orders\Actions\ProductPriceResolver;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Products\Models\Product;

/**
 * Replaces all existing items on an order with a new set,
 * then returns the new subtotal.
 *
 * Used by UpdateOrderHandler when the items array is present in the request.
 * Must be called inside an existing DB transaction.
 *
 * Usage:
 *   $subtotal = $replacer->replace($order, $validated['items']);
 *   $order->update(['total_price' => $subtotal + $order->shipping_price]);
 */
class OrderItemsReplacer
{
    public function __construct(
        private readonly ProductPriceResolver $priceResolver,
    ) {}

    /**
     * Delete old items, create new ones, return the new subtotal.
     *
     * @param  Order                                                    $order
     * @param  array<int, array{product_id: int, quantity: int}>        $items
     * @return float
     */
    public function replace(Order $order, array $items): float
    {
        $order->items()->delete();

        $subtotal = 0.00;

        foreach ($items as $itemData) {
            $product   = Product::findOrFail($itemData['product_id']);
            $qty       = (int) $itemData['quantity'];
            $unitPrice = $this->priceResolver->resolve($product);
            $lineTotal = $unitPrice * $qty;

            OrderItem::create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'product_name' => $product->title,
                'quantity'     => $qty,
                'unit_price'   => $unitPrice,
                'total_price'  => $lineTotal,
            ]);

            $subtotal += $lineTotal;
        }

        return $subtotal;
    }
}