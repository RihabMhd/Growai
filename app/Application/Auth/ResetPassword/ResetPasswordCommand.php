<?php

namespace Application\Auth\ResetPassword;

class ResetPasswordCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $token,
        public readonly string $password,
    ) {}
}