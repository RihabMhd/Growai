<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'platform',
        'shopify_domain',
        'access_token',
        'is_active',
        'boutique_name',
        'last_synced_at',
        'webhook_secret',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    // Never expose the raw access token or webhook secret in JSON responses
    protected $hidden = [
        'access_token',
        'webhook_secret',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}