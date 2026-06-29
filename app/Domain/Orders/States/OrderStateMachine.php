<?php

namespace App\Domain\Orders\States;

use App\Domain\Orders\Models\Order;

// defines allowed order status transitions
class OrderStateMachine
{

    private const CONFIRMATION_STATUSES = [
        'new', 'confirmed', 'no_response', 'callback',
        'cancelled', 'duplicate', 'wrong_number',
        'pending', 'abandoned', 'recovered',

    ];

    private const TRANSITIONS = [

        'new'          => ['confirmed', 'no_response', 'callback', 'cancelled', 'duplicate', 'wrong_number', 'pending'],
        'no_response' => ['new', 'confirmed', 'callback', 'cancelled', 'duplicate', 'wrong_number'],
        'callback'     => ['new', 'confirmed', 'no_response', 'cancelled', 'duplicate', 'wrong_number'],
        'duplicate'    => ['new', 'confirmed', 'no_response', 'callback', 'cancelled', 'wrong_number'],
        'wrong_number' => ['new', 'confirmed', 'no_response', 'callback', 'cancelled', 'duplicate'],

        'pending'    => ['confirmed', 'cancelled', 'abandoned', 'new', 'no_response', 'callback', 'duplicate', 'wrong_number'],
        'confirmed'  => ['processing', 'cancelled', 'returned', 'new', 'no_response', 'callback', 'duplicate', 'wrong_number'],




        
        'processing' => ['shipped', 'cancelled'],
        'shipped'    => ['delivered', 'returned'],
        'delivered'  => ['returned'],
        'cancelled'  => ['new', 'confirmed', 'no_response', 'callback', 'duplicate', 'wrong_number'],
        'returned'   => [],
        'abandoned'  => ['pending', 'new', 'recovered'],


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
