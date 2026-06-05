<?php

namespace App\Infrastructure\Shopify\Webhooks;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Products\Models\Product;
use App\Domain\Shopify\Models\Shop;
use Illuminate\Support\Facades\Log;

final class OrderWebhookHandler
{
    public function handleCreated(
        Shop $shop,
        array $payload
    ): void {

        $this->upsertOrder(
            $shop,
            $payload
        );
    }

    public function handleUpdated(
        Shop $shop,
        array $payload
    ): void {

        $this->upsertOrder(
            $shop,
            $payload
        );
    }

    public function handleCancelled(
        Shop $shop,
        array $payload
    ): void {

        $order = Order::where(
            'shop_id',
            $shop->id
        )
        ->where(
            'order_number',
            $payload['order_number'] ?? null
        )
        ->first();

        if (!$order) {
            return;
        }

        $order->update([
            'status' => 'cancelled',
        ]);
    }

    public function handlePaid(
        Shop $shop,
        array $payload
    ): void {

        $order = Order::where(
            'shop_id',
            $shop->id
        )
        ->where(
            'order_number',
            $payload['order_number'] ?? null
        )
        ->first();

        if (!$order) {
            return;
        }

        $order->update([
            'financial_status' => 'paid',
        ]);
    }

    public function handleFulfilled(
        Shop $shop,
        array $payload
    ): void {

        $order = Order::where(
            'shop_id',
            $shop->id
        )
        ->where(
            'order_number',
            $payload['order_number'] ?? null
        )
        ->first();

        if (!$order) {
            return;
        }

        $order->update([
            'status' => 'fulfilled',
        ]);
    }

    private function upsertOrder(
        Shop $shop,
        array $payload
    ): Order {

        $order = Order::updateOrCreate(
            [
                'shop_id' => $shop->id,
                'order_number' => $payload['order_number']
                    ?? $payload['name']
                    ?? null,
            ],
            [
                'currency' => $payload['currency'] ?? 'USD',
                'status' => $this->mapStatus($payload),
                'financial_status' => $payload['financial_status'] ?? null,
                'total_price' => $payload['total_price'] ?? 0,
            ]
        );

        $this->syncItems(
            $order,
            $shop,
            $payload['line_items'] ?? []
        );

        Log::info(
            'Shopify order synced',
            [
                'shop_id' => $shop->id,
                'order_id' => $order->id,
            ]
        );

        return $order;
    }

    private function syncItems(
        Order $order,
        Shop $shop,
        array $items
    ): void {

        $order->items()->delete();

        foreach ($items as $item) {

            $product = Product::where(
                'shop_id',
                $shop->id
            )
            ->where(
                'external_product_id',
                (string) ($item['product_id'] ?? '')
            )
            ->first();

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product?->id,
                'product_name' => $item['title'] ?? null,
                'quantity' => (int) ($item['quantity'] ?? 1),
                'unit_price' => (float) ($item['price'] ?? 0),
                'total_price' =>
                    ((float) ($item['price'] ?? 0))
                    * ((int) ($item['quantity'] ?? 1)),
            ]);
        }
    }

    private function mapStatus(
        array $payload
    ): string {

        if (!empty($payload['cancelled_at'])) {
            return 'cancelled';
        }

        if (
            ($payload['fulfillment_status'] ?? null)
            === 'fulfilled'
        ) {
            return 'fulfilled';
        }

        return 'pending';
    }
}