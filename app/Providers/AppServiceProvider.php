<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Orders\Services\OrderAuditLogger;
use App\Domain\Orders\Models\Order;

 
// Repository contracts
use App\Infrastructure\Orders\Repositories\OrderRepositoryInterface;
use App\Infrastructure\Orders\Repositories\ShipmentRepositoryInterface;
use App\Infrastructure\Orders\Repositories\ClientRepositoryInterface;
use App\Infrastructure\Orders\Repositories\OrderSourceRepositoryInterface;
use App\Infrastructure\Orders\Repositories\UserRepositoryInterface;
 
// Eloquent implementations
use App\Infrastructure\Orders\Services\EloquentOrderAuditLogger;
use App\Infrastructure\Orders\Repositories\EloquentOrderRepository;
use App\Infrastructure\Orders\Repositories\EloquentShipmentRepository;
use App\Infrastructure\Orders\Repositories\EloquentClientRepository;
use App\Infrastructure\Orders\Repositories\EloquentOrderSourceRepository;
use App\Infrastructure\Orders\Repositories\EloquentUserRepository;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->bind(OrderAuditLogger::class, EloquentOrderAuditLogger::class);
        $this->app->bind(OrderAuditLogger::class, EloquentOrderAuditLogger::class);
        $this->app->bind(OrderRepositoryInterface::class,       EloquentOrderRepository::class);
        $this->app->bind(ShipmentRepositoryInterface::class,    EloquentShipmentRepository::class);
        $this->app->bind(ClientRepositoryInterface::class,      EloquentClientRepository::class);
        $this->app->bind(OrderSourceRepositoryInterface::class, EloquentOrderSourceRepository::class);
        $this->app->bind(UserRepositoryInterface::class,        EloquentUserRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Order::observe(\App\Observers\OrderObserver::class);
    }
}
