<?php

namespace App\Infrastructure\Delivery\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShipmentStatusModel extends Model
{
    protected $table = 'shipment_statuses';

    protected $fillable = [
        'slug',
        'name',
        'color',
        'position',
        'is_final',
    ];

    protected $casts = [
        'is_final' => 'boolean',
    ];

    public function shipments(): HasMany
    {
        return $this->hasMany(ShipmentModel::class, 'shipment_status_id');
    }
}
