<?php
// app/Providers/CarrierActionServiceProvider.php
// Bind the interface — register in config/app.php providers array.

namespace App\Providers;

use App\Domain\CarrierActions\CarrierActionDefinitionRegistry;
use App\Domain\CarrierActions\Contracts\CarrierActionDefinitionProvider;
use Illuminate\Support\ServiceProvider;

final class CarrierActionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CarrierActionDefinitionProvider::class, CarrierActionDefinitionRegistry::class);
    }
}