<?php

namespace App\Infrastructure\Delivery\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarrierWebhookLogModel extends Model
{
    public $timestamps = false;

    protected $table = 'carrier_webhook_logs';

    protected $fillable = [
        'delivery_company_id',
        'event',
        'payload',
        'processed',
        'error',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function deliveryCompany(): BelongsTo
    {
        return $this->belongsTo(DeliveryCompanyModel::class, 'delivery_company_id');
    }
}
