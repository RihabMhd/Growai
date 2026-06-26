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
            LogOrderHistory::class,
        ],
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        \App\Domain\Orders\Events\OrderCreated::class => [
            // add future listeners here
        ],

        \App\Domain\Orders\Events\OrderConfirmed::class => [
            // add future listeners here
        ],
    ];

    public function boot(): void
    {
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
