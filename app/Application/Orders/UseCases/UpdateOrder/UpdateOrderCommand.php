<?php

namespace App\Application\Orders\UseCases\UpdateOrder;

/**
 * Immutable input DTO for the UpdateOrderHandler.
 * All fields are nullable — only non-null fields are applied.
 */
final class UpdateOrderCommand
{
    public function __construct(
        public readonly int|string $orderId,
        public readonly int        $actorId,

        // Order fields
        public readonly ?string $status,
        public readonly ?string $financialStatus,
        public readonly ?string $notes,
        public readonly ?float  $shippingPrice,

        // Client fields
        public readonly ?string $customerName,
        public readonly ?string $customerPhone,
        public readonly ?string $customerEmail,

        // Address fields
        public readonly ?string $province,
        public readonly ?string $city,
        public readonly ?string $street,

        // Items (null = no change)
        public readonly ?array  $items,
    ) {}

    public static function fromArray(int|string $orderId, array $validated, int $actorId): self
    {
        return new self(
            orderId:         $orderId,
            actorId:         $actorId,
            status:          $validated['status']           ?? null,
            financialStatus: $validated['financial_status'] ?? null,
            notes:           $validated['notes']            ?? null,
            shippingPrice:   isset($validated['shipping_price'])
                                ? (float) $validated['shipping_price']
                                : null,
            customerName:    $validated['customer_name']  ?? null,
            customerPhone:   $validated['customer_phone'] ?? null,
            customerEmail:   $validated['customer_email'] ?? null,
            province:        $validated['province']       ?? null,
            city:            $validated['city']           ?? null,
            street:          $validated['street']         ?? null,
            items:           $validated['items']          ?? null,
        );
    }
}