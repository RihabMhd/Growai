<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'dispatch_auto',
        'inactive_strategy',
        'commission_currency'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}