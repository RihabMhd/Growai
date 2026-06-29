<?php

namespace App\Domain\Delivery\Shipment\ValueObjects;

final readonly class ShipmentStatusSlug
{
    public const UNFULFILLED = 'unfulfilled';
    public const LABEL_CREATED = 'label_created';
    public const LABEL_PURCHASED = 'label_purchased';
    public const LABEL_PRINTED = 'label_printed';
    public const CONFIRMED = 'confirmed';
    public const IN_TRANSIT = 'in_transit';
    public const OUT_FOR_DELIVERY = 'out_for_delivery';
    public const DELIVERED = 'delivered';
    public const ATTEMPTED_DELIVERY = 'attempted_delivery';
    public const DELIVERY_FAILED = 'delivery_failed';
    public const DELAYED = 'delayed';
    public const RETURNED = 'returned';
    public const PARTIAL = 'partial';
    public const FULFILLED = 'fulfilled';

    private const FINAL_STATUSES = [
        self::DELIVERED,
        self::DELIVERY_FAILED,
        self::RETURNED,
        self::FULFILLED,
    ];

    private const IN_TRANSIT_STATUSES = [
        self::IN_TRANSIT,
        self::OUT_FOR_DELIVERY,
        self::DELAYED,
        self::ATTEMPTED_DELIVERY,
    ];

    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Shipment status slug cannot be empty.');
        }
    }

    public static function unfulfilled(): self
    {
        return new self(self::UNFULFILLED);
    }

    public static function labelCreated(): self
    {
        return new self(self::LABEL_CREATED);
    }

    public function isFinal(): bool
    {
        return in_array($this->value, self::FINAL_STATUSES, true);
    }

    public function isInTransit(): bool
    {
        return in_array($this->value, self::IN_TRANSIT_STATUSES, true);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
