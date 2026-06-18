<?php

namespace App\Infrastructure\Delivery\Persistence\Eloquent\Models;

use App\Domain\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShipmentModel extends Model
{
    protected $table = 'shipments';

    protected $fillable = [
        'order_id',
        'delivery_company_id',
        'tracking_number',
        'shipment_status_id',
        'recipient_name',
        'recipient_phone',
        'address',
        'city',
        'region',
        'country',
        'cod_amount',
        'delivery_notes',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'cod_amount' => 'float',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function deliveryCompany(): BelongsTo
    {
        return $this->belongsTo(DeliveryCompanyModel::class, 'delivery_company_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ShipmentStatusModel::class, 'shipment_status_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ShipmentHistoryModel::class, 'shipment_id');
    }
}
