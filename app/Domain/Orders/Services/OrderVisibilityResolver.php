<?php

namespace App\Domain\Orders\Services;

use App\Domain\Teams\Models\User;
use Illuminate\Database\Eloquent\Builder;
use App\Domain\Teams\Models\MemberRole;
// admins see all orders, assigned staff see only their products, unassigned staff see all orders
final class OrderVisibilityResolver
{

    public function apply(Builder $query, User $actor): void
    {
        if ($actor->role !== MemberRole::Staff) {
            return;
        }

        $assignedProductIds = $actor->products()->pluck('products.id')->toArray();

        if (empty($assignedProductIds)) {
            return;
        }

        $query->whereHas('items', function (Builder $q) use ($assignedProductIds) {
            $q->whereIn('product_id', $assignedProductIds);
        });
    }


    public function canSee(User $actor, \App\Domain\Orders\Models\Order $order): bool
    {
        if ($actor->role !== MemberRole::Staff) {
            return true;
        }

        $assignedProductIds = $actor->products()->pluck('products.id')->toArray();

        if (empty($assignedProductIds)) {
            return true;
        }

        $orderProductIds = $order->items()->pluck('product_id')->toArray();

        return ! empty(array_intersect($assignedProductIds, $orderProductIds));
    }
}