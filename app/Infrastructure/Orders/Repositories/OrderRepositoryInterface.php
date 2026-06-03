<?php

namespace App\Infrastructure\Orders\Repositories;

use App\Domain\Orders\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Domain contract for order persistence.
 *
 * Bind in AppServiceProvider:
 *   $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
 */
interface OrderRepositoryInterface
{
    /**
     * Return a base query with standard relations eager-loaded.
     * Callers may further filter/sort before calling ->get().
     */
    public function baseQuery(): Builder;

    /**
     * Find an order by ID with full relations for the show/update views.
     */
    public function findWithRelations(int|string $id): Order;

    /**
     * Persist a new order row and return it.
     */
    public function create(array $data): Order;

    /**
     * Update an existing order and return the refreshed model.
     */
    public function update(Order $order, array $data): Order;

    /**
     * Assign an agent without firing model events (updateQuietly).
     */
    public function assignAgent(Order $order, ?int $agentId): void;

    /**
     * Bulk-assign an agent to a set of orders inside a transaction.
     *
     * @param  int[]   $orderIds
     */
    public function bulkAssign(array $orderIds, ?int $agentId): Collection;

    /**
     * Bulk-update status for a set of orders inside a transaction.
     * Returns only the orders whose status actually changed.
     *
     * @param  int[]   $orderIds
     */
    public function bulkUpdateStatus(array $orderIds, string $newStatus): Collection;
}