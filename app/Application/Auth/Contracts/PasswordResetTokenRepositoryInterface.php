<?php

namespace Application\Auth\Contracts;

interface PasswordResetTokenRepositoryInterface
{
    public function upsert(string $email, string $hashedToken): void;

    public function findByEmail(string $email): ?object;

    public function deleteByEmail(string $email): void;
}
