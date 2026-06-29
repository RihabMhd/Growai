<?php

namespace App\Domain\Orders\Services;

use App\Domain\Orders\Models\OrderStatus;

final class OrderStatusNameEnforcer
{
    /**
     * Enforces English-only display names at runtime.
     *
     * NOTE: Do NOT translate dynamically; frontend handles localization.
     */
    public static function englishNameForSlug(string $slug): ?string
    {
        return match ($slug) {
            'new' => 'New',
            'confirmed' => 'Confirmed',
            'no_response' => 'No Response',
            'callback' => 'Callback',
            'cancelled' => 'Cancelled',
            'duplicate' => 'Duplicate',
            'wrong_number' => 'Wrong Number',

            // other statuses (delivery/label/etc.) may already be English.
            default => null,
        };
    }

    public static function enforceOn(OrderStatus $status): void
    {
        $english = self::englishNameForSlug($status->slug);
        if ($english !== null) {
            $status->name = $english;
        }
    }
}

