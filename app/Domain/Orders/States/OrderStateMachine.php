<?php

namespace App\Domain\Orders\States;

use App\Domain\Orders\Models\Order;

/**
 * Defines and enforces allowed order status transitions.
 *
 * Usage:
 *   $machine = new OrderStateMachine($order);
 *   if ($machine->canTransitionTo('confirmed')) { ... }
 *   $machine->transitionTo('confirmed'); // throws if illegal
 */
class OrderStateMachine
{
    /**
     * Allowed transitions: current status → allowed next statuses.
     *
     * 'any' as a key means the transition is allowed from any status.
     */
    private const TRANSITIONS = [
        'pending'    => ['confirmed', 'cancelled', 'abandoned'],
        'confirmed'  => ['processing', 'cancelled', 'returned'],
        'processing' => ['shipped', 'cancelled'],
        'shipped'    => ['delivered', 'returned'],
        'delivered'  => ['returned'],
        'cancelled'  => [],
        'returned'   => [],
        'abandoned'  => ['pending'],
    ];

    public function __construct(
        private readonly Order $order,
    ) {}

    /**
     * Check whether transitioning to $newStatus is allowed.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $current = $this->order->status;

        // Allow staying on the same status (no-op update)
        if ($current === $newStatus) {
            return true;
        }

        $allowed = self::TRANSITIONS[$current] ?? [];

        return in_array($newStatus, $allowed, true);
    }

    /**
     * Transition to $newStatus, throwing if the transition is illegal.
     *
     * @throws \DomainException
     */
    public function transitionTo(string $newStatus): void
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new \DomainException(
                "Transition invalide : '{$this->order->status}' → '{$newStatus}'."
            );
        }

        $this->order->status = $newStatus;
    }

    /**
     * Return all statuses reachable from the current status.
     *
     * @return string[]
     */
    public function allowedTransitions(): array
    {
        return self::TRANSITIONS[$this->order->status] ?? [];
    }
}