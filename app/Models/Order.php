<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Order extends Model
{
    use HasFactory;

    public function scopeForAgent(Builder $query, int $agentId): Builder
    {
        return $query->whereHas('items', function ($sub) use ($agentId) {
            $sub->whereIn('product_id', function ($ids) use ($agentId) {
                $ids->select('product_id')
                    ->from('product_user')
                    ->where('user_id', $agentId);
            });
        });
    }

    protected $fillable = [
        'shop_id',
        'client_id',
        'order_number',
        'customer_name',
        'customer_phone',
        'total_price',
        'shipping_price',
        'discount',
        'currency',
        'status',
        'financial_status',
        'notes',
        'is_abandoned',
        'abandoned_at',
        'assigned_to',
        'commission_paid'
    ];

    protected $casts = [
        'is_abandoned' => 'boolean',
        'abandoned_at' => 'datetime',
        'commission_paid' => 'boolean',
        'total_price' => 'float',
        'shipping_price' => 'float',
        'discount' => 'float'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function sources()
    {
        return $this->hasMany(OrderSource::class);
    }

    public function histories()
    {
        return $this->hasMany(OrderHistory::class);
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
    
    public function statusModel() { 
        return $this->belongsTo(OrderStatus::class, 'status', 'slug'); 
    }


}