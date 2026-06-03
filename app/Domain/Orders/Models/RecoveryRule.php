<?php

namespace App\Domain\Orders\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class RecoveryRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'name',
        'trigger_condition',
        'action',
        'delay_minutes',
        'is_active'
    ];

    protected $casts = [
        'trigger_condition' => 'array',
        'is_active' => 'boolean'
    ];
}
