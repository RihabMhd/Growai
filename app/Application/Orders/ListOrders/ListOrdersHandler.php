<?php

namespace App\Application\Orders\ListOrders;

use App\Domain\Orders\Actions\OrderMetricsCalculator;
use App\Domain\Orders\Services\OrderVisibilityResolver;
use App\Infrastructure\Orders\Repositories\OrderRepositoryInterface;
use App\Infrastructure\Orders\Repositories\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class ListOrdersHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface  $orders,
        private readonly UserRepositoryInterface   $users,
        private readonly OrderMetricsCalculator    $metricsCalculator,
        private readonly OrderVisibilityResolver   $visibilityResolver,
    ) {}

    /**
     * @return array{
     *   orders: \Illuminate\Database\Eloquent\Collection,
     *   metrics: array,
     *   active_agents: \Illuminate\Database\Eloquent\Collection|array
     * }
     */
    public function handle(ListOrdersQuery $query): array
    {
        $baseQuery = $this->buildFilteredQuery($query);

        return [
            'orders'        => (clone $baseQuery)->get(),
            'metrics'       => $this->metricsCalculator->calculate(clone $baseQuery),
            'active_agents' => $query->actor->role === 'admin'
                                ? $this->users->activeAgents()
                                : [],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function buildFilteredQuery(ListOrdersQuery $query): Builder
    {
        $builder = $this->orders->baseQuery();

        $this->visibilityResolver->apply($builder, $query->actor);
        $this->applySearch($builder, $query);
        $this->applyTypeFilter($builder, $query);
        $this->applyStatusFilter($builder, $query);

        return $builder;
    }

    private function applySearch(Builder $builder, ListOrdersQuery $query): void
    {
        if ($query->search === null) {
            return;
        }

        $builder->where('order_number', 'like', "%{$query->search}%");
    }

    private function applyTypeFilter(Builder $builder, ListOrdersQuery $query): void
    {
        if ($query->type === null) {
            return;
        }

        $builder->where('is_abandoned', $query->type === 'abandoned');
    }

    private function applyStatusFilter(Builder $builder, ListOrdersQuery $query): void
    {
        if ($query->status === null) {
            return;
        }

        $builder->where('status', $query->status);
    }
}