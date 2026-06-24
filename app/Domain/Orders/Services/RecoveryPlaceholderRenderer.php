<?php

namespace App\Domain\Orders\Services;

use App\Domain\Orders\Models\Order;

final class RecoveryPlaceholderRenderer
{

    public function renderMany(array $values, Order $order): array
    {
        return array_map(fn (string $value) => $this->render($value, $order), $values);
    }

    public function render(string $value, Order $order): string
    {
        $shop = $order->shop;
        $recoveryUrl = rtrim((string) config('app.url'), '/') . "/checkout/recover/{$order->id}";

        return str_replace(
            [
                '{{customer_name}}',
                '{{recovery_url}}',
                '{{total}}',
                '{{currency}}',
                '{{shop_name}}',
            ],
            [
                $order->client?->name ?? '',
                $recoveryUrl,
                number_format((float) $order->total_price, 2, '.', ''),
                $order->currency ?? 'MAD',
                $shop?->boutique_name ?? $shop?->name ?? config('app.name'),
            ],
            $value
        );
    }
}
