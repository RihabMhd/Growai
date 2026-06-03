<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Orders\Services\OrderAuditLogger;
use App\Domain\Orders\Models\Order;
 
// Infrastructure implementations
use App\Infrastructure\Orders\Services\EloquentOrderAuditLogger;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
         $this->app->bind(OrderAuditLogger::class, EloquentOrderAuditLogger::class);
         // Future bindings follow the same pattern:
        // $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
        // $this->app->bind(ShipmentRepositoryInterface::class, EloquentShipmentRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Order::observe(\App\Observers\OrderObserver::class);
    }
}
