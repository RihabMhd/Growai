<?php

namespace App\Providers;

use Application\Auth\Contracts\PasswordResetMailerInterface;
use Application\Auth\Contracts\PasswordResetTokenRepositoryInterface;
use Application\Auth\Contracts\TokenServiceInterface;
use Domain\Auth\Services\Interfaces\PasswordHasherInterface;
use Domain\Auth\Services\Interfaces\TokenGeneratorInterface;
use Illuminate\Support\ServiceProvider;
use Infrastructure\Auth\Hashing\LaravelPasswordHasher;
use Infrastructure\Auth\Mail\LaravelPasswordResetMailer;
use Infrastructure\Auth\ResetTokenStorage\LaravelTokenGenerator;
use Infrastructure\Auth\ResetTokenStorage\PasswordResetTokenRepository;
use Infrastructure\Auth\Token\SanctumTokenService;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PasswordHasherInterface::class, LaravelPasswordHasher::class);
        $this->app->bind(TokenGeneratorInterface::class, LaravelTokenGenerator::class);
        $this->app->bind(TokenServiceInterface::class, SanctumTokenService::class);
        $this->app->bind(PasswordResetTokenRepositoryInterface::class, PasswordResetTokenRepository::class);
        $this->app->bind(PasswordResetMailerInterface::class, LaravelPasswordResetMailer::class);
    }
}
