<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'whatsapp_message',   // legacy single-template fallback
        'auto_send',
        'templates',          // JSON: { "FR": "...", "AR": "...", "Darija FR": "...", ... }
    ];

    protected $casts = [
        'auto_send' => 'boolean',
        'templates' => 'array',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}