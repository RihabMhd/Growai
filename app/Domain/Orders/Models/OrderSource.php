<?php
namespace App\Domain\Orders\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'type',
        'platform',
        'external_id',
        'campaign_name',
        'campaign_id',
        'adset_name',
        'ad_id',
        'utm_source',
        'utm_campaign',
        'raw_payload'
    ];

    protected $casts = [
        'raw_payload' => 'array'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}