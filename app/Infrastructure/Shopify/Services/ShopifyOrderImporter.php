<?php

namespace App\Infrastructure\Shopify\Services;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Products\Models\Product;
use App\Domain\Shopify\Models\Shop;
use Illuminate\Support\Facades\Log;

final readonly class ShopifyOrderImporter
{

    public function sync(Shop $shop, array $orders): array
    {
        $imported = 0;
        $updated  = 0;
        $failed   = 0;

        foreach ($orders as $payload) {
            try {
                $exists = Order::where('shop_id', $shop->id)
                    ->where('external_order_id', (string) $payload['id'])
                    ->exists();

                $this->upsert($shop, $payload);

                if ($exists) {
                    $updated++;
                } else {
                    $imported++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Order sync failed', [
                    'shop_id'           => $shop->id,
                    'external_order_id' => $payload['id'] ?? null,
                    'error'             => $e->getMessage(),
                ]);
            }
        }

        Log::info('Shopify orders synchronized', [
            'shop_id'  => $shop->id,
            'imported' => $imported,
            'updated'  => $updated,
            'failed'   => $failed,
            'total'    => count($orders),
        ]);

        return [
            'success'  => true,
            'imported' => $imported,
            'updated'  => $updated,
            'failed'   => $failed,
            'total'    => count($orders),
        ];
    }

    // single source of truth for shopify order persistence
    public function upsert(Shop $shop, array $payload): Order
    {
        $customer        = $payload['customer'] ?? [];
        $shippingAddress = $payload['shipping_address'] ?? null;
        $billingAddress  = $payload['billing_address'] ?? null;

        $customerName = trim(
            ($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')
        ) ?: ($shippingAddress['name'] ?? $billingAddress['name'] ?? null);

        $order = Order::updateOrCreate(
            [
                'shop_id'           => $shop->id,
                'external_order_id' => (string) $payload['id'],
            ],
            [
                'order_number'       => $payload['order_number'] ?? $payload['name'] ?? null,
                'currency'           => $payload['currency'] ?? 'USD',
                'status'             => $this->mapStatus($payload),
                'financial_status'   => $payload['financial_status'] ?? null,
                'fulfillment_status' => $payload['fulfillment_status'] ?? null,
                'total_price'        => (float) ($payload['total_price'] ?? 0),
                'shipping_price'     => (float) ($payload['total_shipping_price_set']['shop_money']['amount'] ?? 0),
                'customer_name'      => $customerName,
                'customer_email'     => $customer['email'] ?? $payload['email'] ?? null,
                'customer_phone'     => $customer['phone'] ?? $payload['phone'] ?? null,
                'shipping_address'   => $shippingAddress,
                'order_date'         => isset($payload['created_at'])
                    ? \Carbon\Carbon::parse($payload['created_at'])
                    : null,
                'source_channel'     => 'shopify',
            ]
        );

        $this->syncItems($order, $shop, $payload['line_items'] ?? []);

        Log::info('Shopify order synced', [
            'shop_id'           => $shop->id,
            'order_id'          => $order->id,
            'external_order_id' => $order->external_order_id,
        ]);

        return $order;
    }


    public function markCancelled(Shop $shop, string $externalOrderId): void
    {
        $this->findByExternalId($shop, $externalOrderId)
            ?->update(['status' => 'cancelled']);
    }


    public function markPaid(Shop $shop, string $externalOrderId): void
    {
        $this->findByExternalId($shop, $externalOrderId)
            ?->update(['financial_status' => 'paid']);
    }


    public function markFulfilled(Shop $shop, string $externalOrderId): void
    {
        $this->findByExternalId($shop, $externalOrderId)
            ?->update(['status' => 'fulfilled']);
    }

    private function findByExternalId(Shop $shop, string $externalOrderId): ?Order
    {
        return Order::where('shop_id', $shop->id)
            ->where('external_order_id', $externalOrderId)
            ->first();
    }

    private function syncItems(Order $order, Shop $shop, array $items): void
    {
        $order->items()->delete();

        foreach ($items as $item) {
            $product = Product::where('shop_id', $shop->id)
                ->where('external_product_id', (string) ($item['product_id'] ?? ''))
                ->first();

            OrderItem::create([
                'order_id'     => $order->id,
                'product_id'   => $product?->id,
                'product_name' => $item['title'] ?? null,
                'quantity'     => (int) ($item['quantity'] ?? 1),
                'unit_price'   => (float) ($item['price'] ?? 0),
                'total_price'  => (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 1),
            ]);
        }
    }

    private function mapStatus(array $payload): string
    {
        if (!empty($payload['cancelled_at'])) {
            return 'cancelled';
        }

        if (($payload['fulfillment_status'] ?? null) === 'fulfilled') {
            return 'fulfilled';
        }

        return 'nouveau';
    }
}
