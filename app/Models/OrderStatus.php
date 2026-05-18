<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatus extends Model
{
    use HasFactory;

    protected $fillable = ['slug','name','whatsapp_message','auto_send','templates'];

    protected $casts = [
        'auto_send' => 'boolean',
        'templates' => 'array',
    ];
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}