<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Orders\Services\OrderAuditLogger;
use App\Domain\Orders\Models\Order;


// Repository contracts
use App\Infrastructure\Orders\Repositories\OrderRepositoryInterface;
use App\Infrastructure\Orders\Repositories\ShipmentRepositoryInterface;
use App\Infrastructure\Orders\Repositories\OrderSourceRepositoryInterface;
use App\Infrastructure\Orders\Repositories\UserRepositoryInterface;

// Eloquent implementations
use App\Infrastructure\Orders\Services\EloquentOrderAuditLogger;
use App\Infrastructure\Orders\Repositories\EloquentOrderRepository;
use App\Infrastructure\Orders\Repositories\EloquentShipmentRepository;
use App\Infrastructure\Orders\Repositories\EloquentOrderSourceRepository;
use App\Infrastructure\Orders\Repositories\EloquentUserRepository;

use App\Application\Shopify\Contracts\ShopRepositoryInterface;
use App\Application\Shopify\Contracts\ShopifyClientInterface;
use App\Application\Shopify\Contracts\ShopifyOAuthClientInterface;
use App\Application\Shopify\Contracts\ShopifyWebhookProcessorInterface;

use App\Infrastructure\Shopify\Repositories\EloquentShopRepository;
use App\Infrastructure\Shopify\Clients\ShopifyClient;
use App\Infrastructure\Shopify\OAuth\ShopifyOAuthClient;
use App\Infrastructure\Shopify\Webhooks\ShopifyWebhookHandler;

use App\Domain\Shopify\Contracts\ShopifyProductClientInterface;
use App\Infrastructure\Shopify\NullShopifyProductClient;

use App\Domain\Products\Contracts\ProductRepositoryInterface;
use App\Infrastructure\Products\Repositories\EloquentProductRepository;
use App\Infrastructure\WhatsApp\TwilioWhatsAppDriver;
use App\Infrastructure\WhatsApp\WhatsAppServiceInterface;

use App\Domain\Orders\Repositories\OrderStatusRepositoryInterface;
use App\Infrastructure\Orders\Repositories\EloquentOrderStatusRepository;

use App\Domain\Teams\TeamRepositoryInterface;
use App\Infrastructure\Teams\EloquentTeamRepository;

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
        $this->app->bind(
            \App\Domain\Orders\Repositories\ClientRepositoryInterface::class,
            \App\Infrastructure\Orders\Repositories\EloquentClientRepository::class,
        );
        $this->app->bind(OrderSourceRepositoryInterface::class, EloquentOrderSourceRepository::class);
        $this->app->bind(UserRepositoryInterface::class,        EloquentUserRepository::class);
        $this->app->bind(ShopRepositoryInterface::class, EloquentShopRepository::class);
        $this->app->bind(ShopifyClientInterface::class, ShopifyClient::class);
        $this->app->bind(ShopifyOAuthClientInterface::class, ShopifyOAuthClient::class);
        $this->app->bind(ShopifyWebhookProcessorInterface::class, ShopifyWebhookHandler::class);
        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);
        $this->app->bind(ShopifyProductClientInterface::class, NullShopifyProductClient::class);
        $this->app->bind(WhatsAppServiceInterface::class, TwilioWhatsAppDriver::class);
        $this->app->bind(OrderStatusRepositoryInterface::class, EloquentOrderStatusRepository::class);
        $this->app->bind(
            \App\Domain\Teams\TeamRepositoryInterface::class,
            \App\Infrastructure\Team\EloquentTeamRepository::class,
        );
        $this->app->bind(
            \App\Infrastructure\Mail\TeamInvitationMailerInterface::class,
            \App\Infrastructure\Mail\LaravelTeamInvitationMailer::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Order::observe(\App\Observers\OrderObserver::class);
    }
}
