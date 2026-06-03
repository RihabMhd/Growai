<?php

namespace Infrastructure\Auth\ResetTokenStorage;

use Application\Auth\Contracts\PasswordResetTokenRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PasswordResetTokenRepository implements PasswordResetTokenRepositoryInterface
{
    private const TABLE = 'password_reset_tokens';

    public function upsert(string $email, string $hashedToken): void
    {
        DB::table(self::TABLE)->updateOrInsert(
            ['email' => $email],
            [
                'email'      => $email,
                'token'      => $hashedToken,
                'created_at' => now(),
            ]
        );
    }

    public function findByEmail(string $email): ?object
    {
        return DB::table(self::TABLE)->where('email', $email)->first();
    }

    public function deleteByEmail(string $email): void
    {
        DB::table(self::TABLE)->where('email', $email)->delete();
    }
}
