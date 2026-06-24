<?php

namespace App\Infrastructure\Orders\Repositories;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderSource;


interface OrderSourceRepositoryInterface
{

    public function recordForOrder(Order $order, ?string $sourceType): ?OrderSource;
}