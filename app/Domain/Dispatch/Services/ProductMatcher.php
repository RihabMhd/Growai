<?php

namespace App\Domain\Dispatch\Services;

use Illuminate\Support\Collection;

final class ProductMatcher
{
    // agents with no assigned products are eligible for all orders
    // agents with assigned products must share at least one product with the order
    public function filter(Collection $agents, array $orderProductIds): Collection
    {
        return $agents->filter(
            fn ($agent) => $this->isEligible($agent, $orderProductIds)
        );
    }

    private function isEligible($agent, array $orderProductIds): bool
    {
        $assignedProductIds = $agent->products->pluck('id')->toArray();

        if (empty($assignedProductIds)) {
            return true;
        }

        return ! empty(array_intersect($orderProductIds, $assignedProductIds));
    }
}