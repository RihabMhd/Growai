<?php

namespace Application\Auth\Login;

use App\Models\User;
use Application\Auth\Contracts\TokenServiceInterface;
use Domain\Auth\Exceptions\AccountDisabledException;
use Domain\Auth\Exceptions\InvalidCredentialsException;
use Domain\Auth\Services\Interfaces\PasswordHasherInterface;

class LoginHandler
{
    public function __construct(
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly TokenServiceInterface $tokenService,
    ) {}

    /**
     * @throws InvalidCredentialsException
     * @throws AccountDisabledException
     */
    public function handle(LoginCommand $command): array
    {
        $user = User::where('email', $command->email)->first();

        if (!$user || !$this->passwordHasher->verify($command->password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        if (!$user->is_active) {
            throw new AccountDisabledException();
        }

        $token = $this->tokenService->issue($user);
        $user->load('products');

        return [
            'token' => $token,
            'user'  => $user,
        ];
    }
}