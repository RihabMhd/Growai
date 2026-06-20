<?php

namespace App\Domain\Orders\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class RecoveryRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'template_id',
        'name',
        'trigger_condition',
        'action',
        'delay_minutes',
        'message_template',
        'is_active'
    ];

    protected $casts = [
        'trigger_condition' => 'array',
        'is_active' => 'boolean'
    ];
}
