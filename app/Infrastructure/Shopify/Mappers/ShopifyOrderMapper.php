<?php

namespace App\Infrastructure\Shopify\Mappers;

use App\Domain\Shopify\DTOs\ShopifyOrderDTO;

final class ShopifyOrderMapper
{
    public function toDto(array $order): ShopifyOrderDTO
    {
        return new ShopifyOrderDTO(
            id: (string) $order['id'],
            orderNumber: $order['order_number']
                ?? $order['name']
                ?? null,

            customerEmail: $order['email']
                ?? ($order['customer']['email'] ?? null),

            customerName: $this->buildCustomerName(
                $order['customer'] ?? null
            ),

            customerPhone: $order['phone']
                ?? ($order['customer']['phone'] ?? null),

            status: $this->mapStatus($order),

            paymentStatus: $order['financial_status']
                ?? null,

            fulfillmentStatus: $order['fulfillment_status']
                ?? null,

            totalPrice: (float) (
                $order['total_price']
                ?? 0
            ),

            currency: $order['currency']
                ?? 'USD',

            items: $this->mapItems(
                $order['line_items'] ?? []
            ),

            payload: $order
        );
    }

    private function mapItems(array $items): array
    {
        return array_map(
            fn(array $item) => [
                'id' => (string) ($item['id'] ?? ''),
                'product_id' => (string) ($item['product_id'] ?? ''),
                'variant_id' => (string) ($item['variant_id'] ?? ''),
                'title' => $item['title'] ?? null,
                'sku' => $item['sku'] ?? null,
                'quantity' => (int) ($item['quantity'] ?? 1),
                'price' => (float) ($item['price'] ?? 0),
            ],
            $items
        );
    }

    private function buildCustomerName(
        ?array $customer
    ): ?string {

        if (!$customer) {
            return null;
        }

        $parts = array_filter([
            $customer['first_name'] ?? null,
            $customer['last_name'] ?? null,
        ]);

        return implode(' ', $parts);
    }

    private function mapStatus(
        array $order
    ): string {

        if (!empty($order['cancelled_at'])) {
            return 'cancelled';
        }

        if (
            ($order['fulfillment_status'] ?? null)
            === 'fulfilled'
        ) {
            return 'fulfilled';
        }

        if (
            ($order['fulfillment_status'] ?? null)
            === 'partial'
        ) {
            return 'partial';
        }

        return 'new';
    }
}