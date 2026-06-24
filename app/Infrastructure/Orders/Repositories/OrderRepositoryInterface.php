<?php

namespace App\Infrastructure\Orders\Repositories;

use App\Domain\Orders\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;


interface OrderRepositoryInterface
{

    public function baseQuery(): Builder;


    public function findWithRelations(int|string $id): Order;


    public function create(array $data): Order;


    public function update(Order $order, array $data): Order;


    public function assignAgent(Order $order, ?int $agentId): void;


    public function bulkAssign(array $orderIds, ?int $agentId): Collection;

    // returns only orders whose status actually changed
    public function bulkUpdateStatus(array $orderIds, string $newStatus): Collection;
}