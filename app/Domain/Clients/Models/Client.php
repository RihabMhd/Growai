<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

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
