<?php

namespace Infrastructure\Auth\ResetTokenStorage;

use Domain\Auth\Services\Interfaces\TokenGeneratorInterface;
use Illuminate\Support\Str;

class LaravelTokenGenerator implements TokenGeneratorInterface
{
    public function generate(): string
    {
        return Str::random(64);
    }

    public function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }
}
