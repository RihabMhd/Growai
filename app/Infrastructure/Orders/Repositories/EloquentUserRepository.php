<?php

namespace App\Infrastructure\Orders\Repositories;

use App\Domain\Teams\Models\User;
use Illuminate\Database\Eloquent\Collection;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function activeAgents(): Collection
    {
        return User::where('role', 'staff')
            ->where('is_active', true)
            ->get();
    }

    public function find(int $id): ?User
    {
        return User::find($id);
    }
}