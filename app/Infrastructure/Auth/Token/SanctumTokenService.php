<?php

namespace Infrastructure\Auth\Token;

use App\Domain\Teams\Models\User;
use Application\Auth\Contracts\TokenServiceInterface;

class SanctumTokenService implements TokenServiceInterface
{
    public function issue(User $user): string
    {
        return $user->createToken('auth_token')->plainTextToken;
    }

    public function revokeCurrent(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
