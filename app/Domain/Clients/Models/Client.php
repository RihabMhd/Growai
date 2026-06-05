<?php

namespace App\Domain\Clients\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Domain\Orders\Models\Order;
use App\Domain\WhatsApp\Models\Message;
class Client extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\ClientFactory::new();
    }

    protected $fillable = [
        'name',
        'phone',
        'email',
        'city',
        'address',
        'notes',
    ];

    /**
     * Relationship used by OrderController when loading orders with client.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'client_id');
    }
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
