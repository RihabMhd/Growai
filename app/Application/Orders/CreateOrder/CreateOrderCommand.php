<?php

namespace App\Application\Orders\CreateOrder;

/**
 * Immutable input DTO for the CreateOrderHandler.
 * Built from validated request data in OrderController::store().
 */
final class CreateOrderCommand
{
    public function __construct(
        // Customer
        public readonly string  $customerName,
        public readonly string  $customerPhone,
        public readonly ?string $customerEmail,

        // Address
        public readonly ?string $province,
        public readonly ?string $city,
        public readonly ?string $street,

        // Order meta
        public readonly string  $source,
        public readonly ?string $notes,
        public readonly bool    $isAbandoned,
        public readonly float   $shippingPrice,

        // Items: array<int, array{product_id: int, quantity: int}>
        public readonly array   $items,

        // Actor
        public readonly int     $createdByUserId,
    ) {}

    public static function fromArray(array $validated, int $userId): self
    {
        return new self(
            customerName:    $validated['customer_name'],
            customerPhone:   $validated['customer_phone'],
            customerEmail:   $validated['customer_email'] ?? null,
            province:        $validated['province'] ?? null,
            city:            $validated['city'] ?? null,
            street:          $validated['street'] ?? null,
            source:          $validated['source'] ?? 'manual',
            notes:           $validated['notes'] ?? null,
            isAbandoned:     (bool) ($validated['is_abandoned'] ?? false),
            shippingPrice:   (float) ($validated['shipping_price'] ?? 0),
            items:           $validated['items'],
            createdByUserId: $userId,
        );
    }
}