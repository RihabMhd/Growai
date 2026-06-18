<?php

namespace App\Infrastructure\Delivery\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryCompanyModel extends Model
{
    protected $table = 'delivery_companies';

    protected $fillable = [
        'name',
        'slug',
        'phone',
        'api_url',
        'api_key',
        'credentials',
        'is_active',
        'webhook_enabled',
        'webhook_registered_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'webhook_enabled' => 'boolean',
        'webhook_registered_at' => 'datetime',
    ];

    public function shipments(): HasMany
    {
        return $this->hasMany(ShipmentModel::class, 'delivery_company_id');
    }

    public function configurations(): HasMany
    {
        return $this->hasMany(CarrierConfigurationModel::class, 'delivery_company_id');
    }

    public function webhookLogs(): HasMany
    {
        return $this->hasMany(CarrierWebhookLogModel::class, 'delivery_company_id');
    }
}
