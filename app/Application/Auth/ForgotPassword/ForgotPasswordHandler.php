<?php

namespace Application\Auth\ForgotPassword;

use App\Models\User;
use Application\Auth\Contracts\PasswordResetMailerInterface;
use Application\Auth\Contracts\PasswordResetTokenRepositoryInterface;
use Domain\Auth\Services\Interfaces\TokenGeneratorInterface;

class ForgotPasswordHandler
{
    public function __construct(
        private readonly TokenGeneratorInterface $tokenGenerator,
        private readonly PasswordResetTokenRepositoryInterface $tokenRepository,
        private readonly PasswordResetMailerInterface $mailer,
    ) {}

    public function handle(ForgotPasswordCommand $command): void
    {
        $user = User::where('email', $command->email)->first();

        if (!$user) {
            return;
        }

        $plainToken  = $this->tokenGenerator->generate();
        $hashedToken = $this->tokenGenerator->hash($plainToken);

        $this->tokenRepository->upsert($command->email, $hashedToken);

        $this->mailer->send($command->email, $plainToken);
    }
}