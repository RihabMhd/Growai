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
    /**
     * All confirmation-phase statuses used by the frontend.
     * Transitions between them are unrestricted (agents can freely re-classify).
     */
    private const CONFIRMATION_STATUSES = [
        'nouveau', 'confirmed', 'no_response', 'rappel',
        'cancelled', 'doublon', 'wrong_number',
        // legacy slugs kept for backward compat
        'pending', 'no_answer', 'callback', 'duplicate', 'wrong_num',
    ];

    private const TRANSITIONS = [
        // ── Confirmation statuses (free-flow between any of them) ──────────────
        'nouveau'      => ['confirmed', 'no_response', 'rappel', 'cancelled', 'doublon', 'wrong_number', 'pending'],
        'no_response'  => ['nouveau', 'confirmed', 'rappel', 'cancelled', 'doublon', 'wrong_number'],
        'rappel'       => ['nouveau', 'confirmed', 'no_response', 'cancelled', 'doublon', 'wrong_number'],
        'doublon'      => ['nouveau', 'confirmed', 'no_response', 'rappel', 'cancelled', 'wrong_number'],
        'wrong_number' => ['nouveau', 'confirmed', 'no_response', 'rappel', 'cancelled', 'doublon'],
        // legacy slug aliases
        'no_answer'    => ['nouveau', 'confirmed', 'rappel', 'cancelled', 'doublon', 'wrong_number', 'no_response', 'abandoned'],
        'callback'     => ['nouveau', 'confirmed', 'no_response', 'cancelled', 'doublon', 'wrong_number', 'rappel'],
        'duplicate'    => ['nouveau', 'confirmed', 'no_response', 'rappel', 'cancelled', 'wrong_number', 'doublon'],
        'wrong_num'    => ['nouveau', 'confirmed', 'no_response', 'rappel', 'cancelled', 'doublon', 'wrong_number'],

        // ── Delivery-phase statuses (directed flow) ────────────────────────────
        'pending'    => ['confirmed', 'cancelled', 'abandoned', 'nouveau', 'no_response', 'rappel', 'doublon', 'wrong_number'],
        'confirmed'  => ['processing', 'cancelled', 'returned', 'nouveau', 'no_response', 'rappel', 'doublon', 'wrong_number'],
        'processing' => ['shipped', 'cancelled'],
        'shipped'    => ['delivered', 'returned'],
        'delivered'  => ['returned'],
        'cancelled'  => ['nouveau', 'confirmed', 'no_response', 'rappel', 'doublon', 'wrong_number'],
        'returned'   => [],
        'abandoned'  => ['pending', 'nouveau', 'recovered'],
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
