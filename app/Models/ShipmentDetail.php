<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'recipient_name',
        'recipient_phone',
        'address',
        'city',
        'region',
        'cod_amount',
        'delivery_notes'
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}