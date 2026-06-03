<?php

namespace Application\Auth\SocialLogin;

use Domain\Auth\DTOs\SocialUserDTO;

class SocialLoginCommand
{
    public function __construct(
        public readonly SocialUserDTO $socialUser,
    ) {}
}