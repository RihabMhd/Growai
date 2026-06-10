<?php

namespace App\Infrastructure\Orders\Repositories;

use App\Domain\Orders\Repositories\OrderStatusRepositoryInterface;
use App\Domain\Orders\Models\OrderStatus;
use Illuminate\Support\Collection;

class EloquentOrderStatusRepository implements OrderStatusRepositoryInterface
{
    public function all(): Collection
    {
        return OrderStatus::orderBy('name')->get();
    }

    public function findById(int $id): OrderStatus
    {
        return OrderStatus::findOrFail($id);
    }

    public function save(OrderStatus $status): void
    {
        $status->save();
    }

    public function delete(OrderStatus $status): void
    {
        $status->delete();
    }
}