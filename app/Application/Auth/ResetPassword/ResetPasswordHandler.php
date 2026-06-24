<?php

namespace Application\Auth\ResetPassword;

use App\Domain\Teams\Models\User;
use Application\Auth\Contracts\PasswordResetTokenRepositoryInterface;
use Carbon\Carbon;
use Domain\Auth\Exceptions\InvalidTokenException;
use Domain\Auth\Exceptions\TokenExpiredException;
use Domain\Auth\Services\Interfaces\PasswordHasherInterface;
use Domain\Auth\Services\Interfaces\TokenGeneratorInterface;

class ResetPasswordHandler
{
    private const EXPIRY_MINUTES = 60;

    public function __construct(
        private readonly PasswordResetTokenRepositoryInterface $tokenRepository,
        private readonly TokenGeneratorInterface $tokenGenerator,
        private readonly PasswordHasherInterface $passwordHasher,
    ) {}


    public function handle(ResetPasswordCommand $command): void
    {
        $record = $this->tokenRepository->findByEmail($command->email);

        if (!$record) {
            throw new InvalidTokenException();
        }

        if ($this->tokenGenerator->hash($command->token) !== $record->token) {
            throw new InvalidTokenException();
        }

        if (Carbon::parse($record->created_at)->addMinutes(self::EXPIRY_MINUTES)->isPast()) {
            throw new TokenExpiredException();
        }

        $user = User::where('email', $command->email)->firstOrFail();

        $user->update([
            'password' => $this->passwordHasher->hash($command->password),
        ]);

        $this->tokenRepository->deleteByEmail($command->email);
    }
}