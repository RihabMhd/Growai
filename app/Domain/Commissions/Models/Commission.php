<?php

namespace App\Domain\Commissions\Models;

use Illuminate\Database\Eloquent\Model;
use App\Domain\Orders\Models\Order;
use App\Domain\Teams\Models\User;

class Commission extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'amount',
        'type',
        'trigger_status',
        'state',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}