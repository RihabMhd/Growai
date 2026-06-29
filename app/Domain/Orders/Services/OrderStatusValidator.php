<?php
namespace App\Domain\Orders\Services;


use App\Domain\Orders\Repositories\OrderStatusRepositoryInterface;
use App\Domain\Orders\Models\OrderStatus;
use DomainException;

class OrderStatusValidator
{
    public function __construct(
        private readonly OrderStatusRepositoryInterface $orderStatuses
    ) {}

    public function assertExists(string $statusSlug): void
    {
        $statusSlug = trim($statusSlug);

        if ($statusSlug === '') {
            throw new DomainException('Order status cannot be empty.');
        }

        $exists = $this->orderStatuses
            ->all()
            ->contains(fn (OrderStatus $s) => $s->slug === $statusSlug);

        if (! $exists) {
            throw new DomainException("Invalid order status slug '{$statusSlug}'.");
        }
    }
}

