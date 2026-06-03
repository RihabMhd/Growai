<?php

namespace Application\Auth\ForgotPassword;

class ForgotPasswordCommand
{
    public function __construct(
        public readonly string $email,
    ) {}
}