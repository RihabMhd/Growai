<?php

namespace App\Providers;

use App\Events\OrderStatusChanged;
use App\Listeners\SendWhatsappNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Listeners\ProcessCommission;
use App\Listeners\LogOrderHistory;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \App\Domain\Orders\Events\OrderStatusChanged::class => [
            ProcessCommission::class,
            SendWhatsappNotification::class,
            LogOrderHistory::class, // Only ONE instance
        ],
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        \App\Domain\Orders\Events\OrderCreated::class => [
            // Add listeners here if needed
        ],
        \App\Domain\Orders\Events\OrderConfirmed::class => [
            // Add listeners here if needed
        ],
    ];

    public function boot(): void
    {
        // Register the observer
        \App\Domain\Orders\Models\Order::observe(\App\Observers\OrderObserver::class);
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}