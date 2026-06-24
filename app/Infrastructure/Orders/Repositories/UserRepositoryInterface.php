<?php

namespace App\Infrastructure\Orders\Repositories;

use App\Domain\Teams\Models\User;
use Illuminate\Database\Eloquent\Collection;


interface UserRepositoryInterface
{

    public function activeAgents(): Collection;


    public function find(int $id): ?User;
}