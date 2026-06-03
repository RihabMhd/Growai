<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Application\Auth\Contracts\TokenServiceInterface;
use Application\Auth\Login\LoginCommand;
use Application\Auth\Login\LoginHandler;
use Domain\Auth\Exceptions\AccountDisabledException;
use Domain\Auth\Exceptions\InvalidCredentialsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly LoginHandler $loginHandler,
        private readonly TokenServiceInterface $tokenService,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $result = $this->loginHandler->handle(
                new LoginCommand($data['email'], $data['password'])
            );

            return response()->json([
                'message' => 'Login successful',
                'data'    => $result,
            ]);

        } catch (InvalidCredentialsException $e) {
            return response()->json(['message' => $e->getMessage()], 401);

        } catch (AccountDisabledException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user?->load('products');

        return response()->json(['user' => $user]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->tokenService->revokeCurrent($request->user());

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'avatar'   => ['nullable', 'string'],
        ]);

        $request->user()->update($validated);

        return response()->json([
            'message' => 'Profil mis à jour avec succès.',
            'user'    => $request->user()->fresh('products'),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => app(\Domain\Auth\Services\Interfaces\PasswordHasherInterface::class)
                ->hash($validated['password']),
        ]);

        return response()->json(['message' => 'Mot de passe mis à jour avec succès.']);
    }

    public function toggle2FA(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->update(['two_factor_enabled' => !$user->two_factor_enabled]);

        return response()->json([
            'message'             => $user->two_factor_enabled
                ? 'Authentification à deux facteurs activée.'
                : 'Authentification à deux facteurs désactivée.',
            'two_factor_enabled'  => $user->two_factor_enabled,
        ]);
    }
}
