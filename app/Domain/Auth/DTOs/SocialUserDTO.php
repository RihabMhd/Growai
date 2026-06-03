<?php

namespace Domain\Auth\DTOs;

class SocialUserDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $avatar,
        public readonly string $provider,
        public readonly string $providerId,
    ) {}
}