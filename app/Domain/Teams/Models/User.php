<?php

namespace App\Domain\Teams\Models;

use Laravel\Sanctum\HasApiTokens;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Domain\Products\Models\Product;
use App\Domain\Orders\Models\OrderHistory;
use App\Domain\WhatsApp\Models\Message;
use App\Domain\Teams\Models\Team;
use App\Domain\Teams\Models\MemberRole;
use App\Domain\Orders\Models\Order;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'quota',
        'is_dispatch_active',
        'commission_trigger',
        'commission_amount',
        'commission_type',
        'wallet_balance',
        'avatar',
        'whatsapp',
        'two_factor_enabled',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'avatar_url',
    ];


    /**
     * Get user avatar or fallback to a gorgeous illustration based on their name.
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return $this->avatar;
        }
        return "https://api.dicebear.com/7.x/lorelei/svg?seed=" . urlencode($this->name);
    }



    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role'               => MemberRole::class,
            'is_dispatch_active' => 'boolean',
            'wallet_balance' => 'float',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
        ];
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function orderHistories()
    {
        return $this->hasMany(OrderHistory::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'assigned_to');
    }

    public function isAgent(): bool
    {
        return $this->role === MemberRole::Staff;
    }

    public function isAdmin(): bool
    {
        return $this->role === MemberRole::Admin;
    }
}
