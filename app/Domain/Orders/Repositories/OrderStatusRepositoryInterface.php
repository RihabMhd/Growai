<?php

namespace App\Domain\Orders\Repositories;

use App\Domain\Orders\Models\OrderStatus;
use Illuminate\Support\Collection;

interface OrderStatusRepositoryInterface
{
    public function all(): Collection;

    public function findById(int $id): OrderStatus;

    public function save(OrderStatus $status): void;

    public function delete(OrderStatus $status): void;
}