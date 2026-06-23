<?php

namespace App\Domain\Orders\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Domain\Clients\Models\Client;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\OrderStatus;
use App\Domain\Shopify\Models\Shop;
use App\Domain\Teams\Models\User;
use App\Domain\WhatsApp\Models\Message;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\ShipmentModel;

class Order extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\OrderFactory::new();
    }

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

    // Add to $fillable array in app/Domain/Orders/Models/Order.php
    protected $fillable = [
        'shop_id',
        'client_id',
        'assigned_to',
        'external_order_id',      // ← new
        'order_number',
        'total_price',
        'shipping_price',
        'discount',
        'currency',
        'status',
        'financial_status',
        'fulfillment_status',     // ← new
        'commission_paid',
        'is_abandoned',
        'abandoned_at',
        'notes',
        'source_channel',
        'customer_name',          // ← new
        'customer_email',         // ← new
        'customer_phone',         // ← new
        'shipping_address',       // ← new
        'order_date',             // ← new
        'province',
        'city',
        'street',
    ];

    // Add to $casts
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_abandoned'     => 'boolean',
        'abandoned_at'     => 'datetime',
        'commission_paid'  => 'boolean',
        'total_price'      => 'float',
        'shipping_price'   => 'float',
        'discount'         => 'float',
        'shipping_address' => 'array',   // ← new
        'order_date'       => 'datetime', // ← new
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
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
        return $this->hasMany(ShipmentModel::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function statusModel()
    {
        return $this->belongsTo(OrderStatus::class, 'status', 'slug');
    }

    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->status)) {
                $order->status = 'nouveau';
            }
        });
    }
}
