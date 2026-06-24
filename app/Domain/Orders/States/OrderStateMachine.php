<?php

namespace App\Domain\Orders\States;

use App\Domain\Orders\Models\Order;

// defines allowed order status transitions
class OrderStateMachine
{

    private const CONFIRMATION_STATUSES = [
        'nouveau', 'confirmed', 'no_response', 'rappel',
        'cancelled', 'doublon', 'wrong_number',
        // keep legacy slugs for backwards compatibility
        'pending', 'no_answer', 'callback', 'duplicate', 'wrong_num',
    ];

    private const TRANSITIONS = [

        'nouveau'      => ['confirmed', 'no_response', 'rappel', 'cancelled', 'doublon', 'wrong_number', 'pending'],
        'no_response'  => ['nouveau', 'confirmed', 'rappel', 'cancelled', 'doublon', 'wrong_number'],
        'rappel'       => ['nouveau', 'confirmed', 'no_response', 'cancelled', 'doublon', 'wrong_number'],
        'doublon'      => ['nouveau', 'confirmed', 'no_response', 'rappel', 'cancelled', 'wrong_number'],
        'wrong_number' => ['nouveau', 'confirmed', 'no_response', 'rappel', 'cancelled', 'doublon'],

        'no_answer'    => ['nouveau', 'confirmed', 'rappel', 'cancelled', 'doublon', 'wrong_number', 'no_response', 'abandoned'],
        'callback'     => ['nouveau', 'confirmed', 'no_response', 'cancelled', 'doublon', 'wrong_number', 'rappel'],
        'duplicate'    => ['nouveau', 'confirmed', 'no_response', 'rappel', 'cancelled', 'wrong_number', 'doublon'],
        'wrong_num'    => ['nouveau', 'confirmed', 'no_response', 'rappel', 'cancelled', 'doublon', 'wrong_number'],


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


    public function canTransitionTo(string $newStatus): bool
    {
        $current = $this->order->status;

        // ignore updates to the same status
        if ($current === $newStatus) {
            return true;
        }

        $allowed = self::TRANSITIONS[$current] ?? [];

        return in_array($newStatus, $allowed, true);
    }


    public function transitionTo(string $newStatus): void
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new \DomainException(
                "Transition invalide : '{$this->order->status}' → '{$newStatus}'."
            );
        }

        $this->order->status = $newStatus;
    }


    public function allowedTransitions(): array
    {
        return self::TRANSITIONS[$this->order->status] ?? [];
    }
}
