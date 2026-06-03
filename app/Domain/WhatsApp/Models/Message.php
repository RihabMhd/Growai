<?php

namespace App\Domain\WhatsApp\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Domain\Clients\Models\Client;
use App\Domain\Orders\Models\Order;
use App\Domain\Teams\Models\User;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'order_id',
        'user_id',
        'channel',
        'direction',
        'message',
        'status',
        'sent_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
