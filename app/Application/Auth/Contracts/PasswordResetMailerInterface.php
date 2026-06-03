<?php

namespace Application\Auth\Contracts;

interface PasswordResetMailerInterface
{
    public function send(string $email, string $plainToken): void;
}
