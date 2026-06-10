<?php

namespace App\Domain\Orders\Services;

use App\Domain\Teams\Models\User;
use Illuminate\Database\Eloquent\Builder;
use App\Domain\Teams\Models\MemberRole;
/**
 * Domain service — belongs in Domain/Orders/Services because it encodes
 * a pure business rule about which orders a given actor may see.
 * No HTTP, no session, no infrastructure dependencies.
 *
 * Extracted from ListOrdersHandler so the same rule can be applied
 * in show(), assign(), export(), and any future query handlers
 * without duplication.
 *
 * FlashManager visibility rules:
 *   - Admin          → all orders
 *   - Staff, no products assigned → all orders
 *   - Staff, products assigned    → only orders containing those products
 */
final class OrderVisibilityResolver
{
    /**
     * Apply visibility constraints to an existing Eloquent Builder.
     * Mutates $query in place (consistent with how Laravel scopes work).
     */
    public function apply(Builder $query, User $actor): void
    {
        if ($actor->role !== MemberRole::Staff) {
            return; // admin sees everything
        }

        $assignedProductIds = $actor->products()->pluck('products.id')->toArray();

        if (empty($assignedProductIds)) {
            return; // unassigned staff sees everything
        }

        $query->whereHas('items', function (Builder $q) use ($assignedProductIds) {
            $q->whereIn('product_id', $assignedProductIds);
        });
    }

    /**
     * Returns true if the actor may see the given order.
     * Used for single-order authorization (show, update, etc.).
     */
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