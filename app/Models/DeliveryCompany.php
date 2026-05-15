<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryCompany extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'api_url'
    ];

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }
}
