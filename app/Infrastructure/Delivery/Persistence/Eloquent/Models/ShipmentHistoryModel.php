<?php

namespace App\Infrastructure\Delivery\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentHistoryModel extends Model
{
    public $timestamps = false;

    protected $table = 'shipment_histories';

    protected $fillable = [
        'shipment_id',
        'old_status',
        'new_status',
        'source',
        'description',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(ShipmentModel::class, 'shipment_id');
    }
}
