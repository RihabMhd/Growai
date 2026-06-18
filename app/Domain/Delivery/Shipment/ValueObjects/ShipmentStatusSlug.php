<?php

namespace App\Domain\Delivery\Shipment\ValueObjects;

final readonly class ShipmentStatusSlug
{
    public const LABEL_CREATED = 'label_created';
    public const READY_FOR_PICKUP = 'ready_for_pickup';
    public const PICKED_UP = 'picked_up';
    public const OUT_FOR_DELIVERY = 'out_for_delivery';
    public const DELIVERED = 'delivered';
    public const DELAYED = 'delayed';
    public const FAILURE = 'failure';
    public const RETURNED = 'returned';

    private const FINAL_STATUSES = [
        self::DELIVERED,
        self::FAILURE,
        self::RETURNED,
    ];

    private const IN_TRANSIT_STATUSES = [
        self::PICKED_UP,
        self::OUT_FOR_DELIVERY,
        self::DELAYED,
    ];

    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Shipment status slug cannot be empty.');
        }
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
