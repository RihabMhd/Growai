<?php

namespace App\Domain\Shipments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Domain\Orders\Models\Order;
class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'delivery_company_id',
        'tracking_number',
        'status',
        'recipient_name',
        'recipient_phone',
        'address',
        'city',
        'region',
        'country',
        'cod_amount',
        'delivery_notes',
        'shipped_at',
        'delivered_at'
    ];

    protected $casts = [
        'cod_amount' => 'float',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function deliveryCompany()
    {
        return $this->belongsTo(DeliveryCompany::class);
    }

    /**
     * Check if shipment is in final state
     */
    public function isFinal(): bool
    {
        return in_array($this->status, ['delivered', 'failed', 'returned']);
    }

    /**
     * Check if shipment is in transit
     */
    public function isInTransit(): bool
    {
        return in_array($this->status, ['picked_up', 'in_transit', 'out_for_delivery']);
    }
}