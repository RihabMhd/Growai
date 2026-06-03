<?php

namespace App\Infrastructure\Orders\Repositories;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderSource;

/**
 * Domain contract for order source (channel tracking) persistence.
 *
 * Bind in AppServiceProvider:
 *   $this->app->bind(OrderSourceRepositoryInterface::class, EloquentOrderSourceRepository::class);
 */
interface OrderSourceRepositoryInterface
{
    /**
     * Record the acquisition channel for a newly created order.
     * No-op if $sourceType is null or empty.
     */
    public function recordForOrder(Order $order, ?string $sourceType): ?OrderSource;
}