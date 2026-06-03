<?php

namespace App\Providers;

use App\Events\OrderStatusChanged;
use App\Listeners\SendWhatsappNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        \App\Domain\Orders\Events\OrderStatusChanged::class => [
            \App\Listeners\LogOrderHistory::class,
            \App\Listeners\ProcessCommission::class,
            \App\Listeners\SendWhatsappNotification::class,
        ],,
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        \App\Domain\Orders\Events\OrderCreated::class => [
            // Add future listeners here (e.g. SendWelcomeEmail)
        ],

        \App\Domain\Orders\Events\OrderConfirmed::class => [
            // Add future listeners here
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
