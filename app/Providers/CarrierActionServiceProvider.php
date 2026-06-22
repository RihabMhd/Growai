<?php
// app/Providers/CarrierActionServiceProvider.php
// Bind the interface — register in config/app.php providers array.

namespace App\Providers;

use App\Domain\CarrierActions\CarrierActionDefinitionRegistry;
use App\Domain\CarrierActions\Contracts\CarrierActionDefinitionProvider;
use Illuminate\Support\ServiceProvider;
use App\Infrastructure\Carriers\Contracts\CarrierHttpClientFactory;
use App\Infrastructure\Carriers\CarrierHttpClientFactoryImpl;
final class CarrierActionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CarrierHttpClientFactory::class, CarrierHttpClientFactoryImpl::class);
        
        $this->app->bind(CarrierActionDefinitionProvider::class, CarrierActionDefinitionRegistry::class);
    }
}
