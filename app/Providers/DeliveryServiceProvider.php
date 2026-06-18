<?php

namespace App\Providers;

use App\Domain\Delivery\DeliveryCompany\Repositories\CarrierConfigurationRepositoryInterface;
use App\Domain\Delivery\DeliveryCompany\Repositories\CarrierWebhookLogRepositoryInterface;
use App\Domain\Delivery\DeliveryCompany\Repositories\DeliveryCompanyRepositoryInterface;
use App\Domain\Delivery\Shipment\Repositories\ShipmentHistoryRepositoryInterface;
use App\Domain\Delivery\Shipment\Repositories\ShipmentRepositoryInterface as DomainShipmentRepositoryInterface;
use App\Domain\Delivery\Shipment\Repositories\ShipmentStatusRepositoryInterface;
use App\Infrastructure\Delivery\Carriers\CarrierFactory;
use App\Infrastructure\Delivery\Carriers\CarrierManager;
use App\Infrastructure\Delivery\Persistence\Eloquent\Mappers\DeliveryMapper;
use App\Infrastructure\Delivery\Persistence\Eloquent\Repositories\EloquentCarrierConfigurationRepository;
use App\Infrastructure\Delivery\Persistence\Eloquent\Repositories\EloquentCarrierWebhookLogRepository;
use App\Infrastructure\Delivery\Persistence\Eloquent\Repositories\EloquentDeliveryCompanyRepository;
use App\Infrastructure\Delivery\Persistence\Eloquent\Repositories\EloquentShipmentHistoryRepository;
use App\Infrastructure\Delivery\Persistence\Eloquent\Repositories\EloquentShipmentRepository;
use App\Infrastructure\Delivery\Persistence\Eloquent\Repositories\EloquentShipmentStatusRepository;
use App\Infrastructure\Orders\Repositories\ShipmentRepositoryInterface as OrderShipmentRepositoryInterface;
use App\Infrastructure\Orders\Repositories\EloquentShipmentRepository as OrderEloquentShipmentRepository;
use Illuminate\Support\ServiceProvider;

final class DeliveryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DeliveryMapper::class);

        $this->app->bind(DomainShipmentRepositoryInterface::class, EloquentShipmentRepository::class);
        $this->app->bind(ShipmentHistoryRepositoryInterface::class, EloquentShipmentHistoryRepository::class);
        $this->app->bind(ShipmentStatusRepositoryInterface::class, EloquentShipmentStatusRepository::class);
        $this->app->bind(DeliveryCompanyRepositoryInterface::class, EloquentDeliveryCompanyRepository::class);
        $this->app->bind(CarrierConfigurationRepositoryInterface::class, EloquentCarrierConfigurationRepository::class);
        $this->app->bind(CarrierWebhookLogRepositoryInterface::class, EloquentCarrierWebhookLogRepository::class);

        $this->app->singleton(CarrierFactory::class);
        $this->app->singleton(CarrierManager::class);

        $this->app->bind(OrderShipmentRepositoryInterface::class, OrderEloquentShipmentRepository::class);
    }
}
