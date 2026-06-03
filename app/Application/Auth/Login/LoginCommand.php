<?php

namespace Application\Auth\Login;

class LoginCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {}
}