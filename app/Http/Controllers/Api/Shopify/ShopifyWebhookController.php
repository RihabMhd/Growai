<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ShopifyWebhookController extends Controller
{
    /**
     * Main webhook entry point.
     * Shopify sends the topic in the X-Shopify-Topic header.
     */
    public function handle(Request $request, string $shopDomain): Response
    {
        // 1. Find the shop
        $shop = Shop::where('shopify_domain', $shopDomain)
            ->where('is_active', true)
            ->first();

        if (!$shop) {
            Log::warning('Shopify webhook received for unknown shop', ['domain' => $shopDomain]);
            return response('Shop not found', 404);
        }

        // 2. Verify HMAC signature
        if (!$this->verifyHmac($request, $shop)) {
            Log::warning('Shopify webhook HMAC verification failed', ['shop' => $shopDomain]);
            return response('Unauthorized', 401);
        }

        $topic   = $request->header('X-Shopify-Topic');
        $payload = $request->json()->all();

        Log::info('Shopify webhook received', ['shop' => $shopDomain, 'topic' => $topic]);

        // 3. Dispatch to the correct handler
        match ($topic) {
            'orders/create'    => $this->handleOrderCreated($shop, $payload),
            'orders/updated'   => $this->handleOrderUpdated($shop, $payload),
            'orders/cancelled' => $this->handleOrderCancelled($shop, $payload),
            'orders/paid'      => $this->handleOrderPaid($shop, $payload),
            'orders/fulfilled' => $this->handleOrderFulfilled($shop, $payload),
            'products/update'  => $this->handleProductUpdated($shop, $payload),
            'products/delete'  => $this->handleProductDeleted($shop, $payload),
            default            => Log::info('Shopify webhook topic not handled', ['topic' => $topic]),
        };

        // Shopify expects a fast 200 response
        return response('OK', 200);
    }

    // -------------------------------------------------------------------------
    // ORDER HANDLERS
    // -------------------------------------------------------------------------

    private function handleOrderCreated(Shop $shop, array $data): void
    {
        $this->upsertOrder($shop, $data, 'created');
    }

    private function handleOrderUpdated(Shop $shop, array $data): void
    {
        $this->upsertOrder($shop, $data, 'updated');
    }

    private function handleOrderCancelled(Shop $shop, array $data): void
    {
        $order = Order::where('shop_id', $shop->id)
            ->where('external_order_id', (string) $data['id'])
            ->first();

        if ($order) {
            $order->update([
                'status'        => 'cancelled',
                'cancelled_at'  => $data['cancelled_at'] ?? now(),
                'cancel_reason' => $data['cancel_reason'] ?? null,
            ]);
            Log::info('Order cancelled', ['order_id' => $order->id]);
        }
    }

    private function handleOrderPaid(Shop $shop, array $data): void
    {
        $order = Order::where('shop_id', $shop->id)
            ->where('external_order_id', (string) $data['id'])
            ->first();

        if ($order) {
            $order->update(['payment_status' => 'paid']);
            Log::info('Order marked paid', ['order_id' => $order->id]);
        }
    }

    private function handleOrderFulfilled(Shop $shop, array $data): void
    {
        $order = Order::where('shop_id', $shop->id)
            ->where('external_order_id', (string) $data['id'])
            ->first();

        if ($order) {
            $order->update([
                'status'       => 'fulfilled',
                'fulfilled_at' => now(),
            ]);
            Log::info('Order fulfilled', ['order_id' => $order->id]);
        }
    }

    /**
     * Core upsert logic shared by create & update handlers.
     */
    private function upsertOrder(Shop $shop, array $data, string $action): void
    {
        $shippingAddress = $data['shipping_address'] ?? null;
        $billingAddress  = $data['billing_address']  ?? null;
        $customer        = $data['customer']          ?? null;

        $orderData = [
            'shop_id'          => $shop->id,
            'external_order_id'=> (string) $data['id'],
            'order_number'     => $data['order_number']    ?? $data['name'] ?? null,
            'status'           => $this->mapOrderStatus($data),
            'payment_status'   => $data['financial_status'] ?? null,
            'fulfillment_status'=> $data['fulfillment_status'] ?? null,
            'currency'         => $data['currency']        ?? null,
            'total_price'      => floatval($data['total_price']          ?? 0),
            'subtotal_price'   => floatval($data['subtotal_price']       ?? 0),
            'total_tax'        => floatval($data['total_tax']            ?? 0),
            'total_discounts'  => floatval($data['total_discounts']      ?? 0),
            'total_shipping'   => floatval($data['total_shipping_price_set']['shop_money']['amount'] ?? 0),
            'customer_email'   => $data['email']           ?? $customer['email'] ?? null,
            'customer_name'    => $this->buildCustomerName($customer),
            'customer_phone'   => $data['phone']           ?? $customer['phone'] ?? null,
            'shipping_address' => $shippingAddress,
            'billing_address'  => $billingAddress,
            'note'             => $data['note']            ?? null,
            'tags'             => $data['tags']            ?? null,
            'source_name'      => $data['source_name']     ?? 'shopify',
            'source_type'      => 'shopify',
            'shopify_created_at' => $data['created_at']   ?? null,
            'shopify_updated_at' => $data['updated_at']   ?? null,
        ];

        $order = Order::updateOrCreate(
            ['shop_id' => $shop->id, 'external_order_id' => (string) $data['id']],
            $orderData
        );

        // Sync line items
        if (!empty($data['line_items'])) {
            $this->syncOrderItems($order, $data['line_items'], $shop);
        }

        Log::info("Order {$action}", [
            'shop_id'  => $shop->id,
            'order_id' => $order->id,
            'number'   => $order->order_number,
        ]);
    }

    private function syncOrderItems(Order $order, array $lineItems, Shop $shop): void
    {
        // Delete old items and re-insert (simpler than diffing)
        $order->items()->delete();

        foreach ($lineItems as $item) {
            // Try to resolve the local product
            $product = Product::where('shop_id', $shop->id)
                ->where('external_product_id', (string) ($item['product_id'] ?? ''))
                ->first();

            OrderItem::create([
                'order_id'           => $order->id,
                'product_id'         => $product?->id,
                'external_line_item_id' => (string) $item['id'],
                'external_product_id'   => (string) ($item['product_id'] ?? ''),
                'external_variant_id'   => (string) ($item['variant_id'] ?? ''),
                'title'              => $item['title']         ?? null,
                'variant_title'      => $item['variant_title'] ?? null,
                'sku'                => $item['sku']           ?? null,
                'quantity'           => intval($item['quantity']    ?? 1),
                'price'              => floatval($item['price']     ?? 0),
                'total_discount'     => floatval($item['total_discount'] ?? 0),
                'vendor'             => $item['vendor']        ?? null,
                'requires_shipping'  => $item['requires_shipping'] ?? true,
                'taxable'            => $item['taxable']       ?? true,
                'fulfillment_status' => $item['fulfillment_status'] ?? null,
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // PRODUCT HANDLERS
    // -------------------------------------------------------------------------

    private function handleProductUpdated(Shop $shop, array $data): void
    {
        $product = Product::where('shop_id', $shop->id)
            ->where('external_product_id', (string) $data['id'])
            ->first();

        if (!$product) {
            Log::info('Webhook product/update for unknown product — skipping', ['id' => $data['id']]);
            return;
        }

        $product->update([
            'title'        => $data['title']        ?? $product->title,
            'handle'       => $data['handle']       ?? $product->handle,
            'vendor'       => $data['vendor']       ?? $product->vendor,
            'product_type' => $data['product_type'] ?? $product->product_type,
            'description'  => $data['body_html']    ?? $product->description,
            'status'       => $data['status'] === 'active' ? 'active' : 'inactive',
            'variants'     => $this->extractVariants($data),
            'images'       => $this->extractImageUrls($data),
            'image'        => $this->extractImageUrl($data),
        ]);

        Log::info('Product updated via webhook', ['product_id' => $product->id]);
    }

    private function handleProductDeleted(Shop $shop, array $data): void
    {
        Product::where('shop_id', $shop->id)
            ->where('external_product_id', (string) $data['id'])
            ->update(['status' => 'deleted']);

        Log::info('Product deleted via webhook', ['external_id' => $data['id']]);
    }

    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    /**
     * Verify the HMAC-SHA256 signature Shopify sends on every webhook.
     * The secret is your app's webhook secret (not the access token).
     */
    private function verifyHmac(Request $request, Shop $shop): bool
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');

        if (!$hmacHeader) {
            return false;
        }

        // Use the webhook secret stored on the shop, or fall back to the app-wide secret
        $secret = $shop->webhook_secret ?? config('services.shopify.webhook_secret');

        if (!$secret) {
            Log::warning('No Shopify webhook secret configured');
            return false;
        }

        $calculatedHmac = base64_encode(
            hash_hmac('sha256', $request->getContent(), $secret, true)
        );

        return hash_equals($calculatedHmac, $hmacHeader);
    }

    private function mapOrderStatus(array $data): string
    {
        if (($data['cancelled_at'] ?? null)) return 'cancelled';
        if (($data['fulfillment_status'] ?? null) === 'fulfilled') return 'fulfilled';
        if (($data['fulfillment_status'] ?? null) === 'partial') return 'partial';
        return 'pending';
    }

    private function buildCustomerName(?array $customer): ?string
    {
        if (!$customer) return null;
        $parts = array_filter([$customer['first_name'] ?? null, $customer['last_name'] ?? null]);
        return implode(' ', $parts) ?: null;
    }

    private function extractVariants(array $product): array
    {
        $variants = [];
        foreach ($product['variants'] ?? [] as $v) {
            $variants[] = [
                'title'            => $v['title']             ?? 'Default',
                'sku'              => $v['sku']               ?? null,
                'price'            => floatval($v['price']    ?? 0),
                'compare_at_price' => $v['compare_at_price'] ? floatval($v['compare_at_price']) : null,
                'stock'            => intval($v['inventory_quantity'] ?? 0),
            ];
        }
        return $variants;
    }

    private function extractImageUrl(array $product): ?string
    {
        return $product['image']['src'] ?? $product['images'][0]['src'] ?? null;
    }

    private function extractImageUrls(array $product): array
    {
        return array_values(array_filter(
            array_map(fn($img) => isset($img['src']) ? ['src' => $img['src']] : null,
            $product['images'] ?? [])
        ));
    }
}