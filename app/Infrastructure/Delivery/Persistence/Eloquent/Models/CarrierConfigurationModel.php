<?php

namespace App\Infrastructure\Delivery\Persistence\Eloquent\Models;

use App\Domain\Teams\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarrierConfigurationModel extends Model
{
    protected $table = 'carrier_configurations';

    protected $fillable = [
        'team_id',
        'delivery_company_id',
        'credentials_json',
        'field_mapping_json',
        'auto_create_parcel',
        'webhook_enabled',
    ];

    protected $casts = [
        'credentials_json' => 'array',
        'field_mapping_json' => 'array',
        'auto_create_parcel' => 'boolean',
        'webhook_enabled' => 'boolean',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function deliveryCompany(): BelongsTo
    {
        return $this->belongsTo(DeliveryCompanyModel::class, 'delivery_company_id');
    }
}
