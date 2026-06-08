<?php

namespace App\Domain\Teams\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Domain\Teams\Models\User;
use App\Domain\Teams\Models\WhatsAppLanguage;
class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'dispatch_auto',
        'inactive_strategy',
        'commission_currency',
        'order_prefix',
        'country',
        'exchange_rate',
        'whatsapp_language',       
    ];

    protected $casts = [
        'dispatch_auto'    => 'boolean',
        'exchange_rate'    => 'float',
        'whatsapp_language' => WhatsAppLanguage::class,  
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
