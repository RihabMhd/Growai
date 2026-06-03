<?php

namespace App\Application\Orders\UseCases\GetOrder;

use App\Domain\Orders\Models\Order;
use App\Infrastructure\Orders\Repositories\OrderRepositoryInterface;

class GetOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(int|string $id): Order
    {
        return $this->orders->findWithRelations($id);
    }
}