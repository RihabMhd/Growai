<?php

namespace Application\Auth\Register;

use App\Domain\Teams\Models\User;
use Application\Auth\Contracts\TokenServiceInterface;
use Domain\Auth\Services\Interfaces\PasswordHasherInterface;

class RegisterHandler
{
    public function __construct(
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly TokenServiceInterface $tokenService,
    ) {}

    public function handle(RegisterCommand $command): array
    {
        $user = User::create([
            'team_id'   => 1,
            'name'      => $command->name,
            'email'     => $command->email,
            'password'  => $this->passwordHasher->hash($command->password),
            'is_active' => true,
        ]);

        $token = $this->tokenService->issue($user);

        return [
            'token' => $token,
            'user'  => $user,
        ];
    }
}