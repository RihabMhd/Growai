<?php

namespace App\Domain\Teams\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Domain\Teams\Models\User;
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
        'exchange_rate'
    ];

    protected $casts = [
        'dispatch_auto' => 'boolean',
        'exchange_rate' => 'float',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}