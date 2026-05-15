<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'message_body',
        'trigger_event'
    ];

    public function recoveryRules()
    {
        return $this->hasMany(RecoveryRule::class, 'template_id');
    }
}