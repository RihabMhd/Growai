<?php

namespace Application\Auth\Contracts;

use App\Domain\Teams\Models\User;

interface TokenServiceInterface
{
    public function issue(User $user): string;

    public function revokeCurrent(User $user): void;
}
