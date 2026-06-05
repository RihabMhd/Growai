<?php

namespace App\Domain\Shopify\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessedWebhook extends Model
{
    protected $fillable = [
        'webhook_id',
        'topic',
        'shop_domain',
        'processed_at',
    ];
}