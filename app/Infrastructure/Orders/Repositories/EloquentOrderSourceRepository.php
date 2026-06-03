<?php

namespace App\Infrastructure\Orders\Repositories;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderSource;

class EloquentOrderSourceRepository implements OrderSourceRepositoryInterface
{
    public function recordForOrder(Order $order, ?string $sourceType): ?OrderSource
    {
        if (empty($sourceType)) {
            return null;
        }

        return OrderSource::create([
            'order_id' => $order->id,
            'type'     => $sourceType,
            'platform' => $sourceType,
        ]);
    }
}