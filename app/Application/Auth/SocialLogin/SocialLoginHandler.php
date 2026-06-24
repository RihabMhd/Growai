<?php

namespace Application\Auth\SocialLogin;

use App\Domain\Teams\Models\User;
use Application\Auth\Contracts\TokenServiceInterface;
use Domain\Auth\Exceptions\AccountDisabledException;
use Domain\Auth\Exceptions\InvalidCredentialsException;

class SocialLoginHandler
{
    public function __construct(
        private readonly TokenServiceInterface $tokenService,
    ) {}


    public function handle(SocialLoginCommand $command): array
    {
        $dto  = $command->socialUser;
        $user = User::where('email', $dto->email)->first();

        if (!$user) {
            throw new InvalidCredentialsException();
        }

        if (!$user->is_active) {
            throw new AccountDisabledException();
        }

        $user->update([
            'avatar'      => $dto->avatar,
            'provider'    => $dto->provider,
            'provider_id' => $dto->providerId,
        ]);

        $token = $this->tokenService->issue($user);

        return [
            'token' => $token,
            'user'  => $user,
        ];
    }
}