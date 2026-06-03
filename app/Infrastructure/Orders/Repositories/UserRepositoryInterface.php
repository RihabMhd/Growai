<?php

namespace App\Infrastructure\Orders\Repositories;

use App\Domain\Teams\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Domain contract for user queries needed by the order bounded context.
 *
 * Bind in AppServiceProvider:
 *   $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
 */
interface UserRepositoryInterface
{
    /**
     * Return all active staff users for the agent assignment dropdown.
     * Used in the index response when the requester is an admin.
     */
    public function activeAgents(): Collection;

    /**
     * Find a user by ID, returning null if not found.
     */
    public function find(int $id): ?User;
}