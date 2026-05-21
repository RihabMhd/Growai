<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Login
     */
    public function login(LoginRequest $request)
    {
        $response = $this->authService->login($request->validated());

        return response()->json(
            [
                'message' => $response['message'],
                'data' => $response['data'] ?? null
            ],
            $response['status']
        );
    }

    /**
     * Current authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->load('products');
        }
        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $response = $this->authService->logout($request->user());

        return response()->json([
            'message' => $response['message']
        ], $response['status']);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'whatsapp' => 'nullable|string|max:30',
            'avatar' => 'nullable|string',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profil mis à jour avec succès.',
            'user' => $user->fresh('products')
        ]);
    }

    /**
     * Update user password
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password'])
        ]);

        return response()->json([
            'message' => 'Mot de passe mis à jour avec succès.'
        ]);
    }

    /**
     * Toggle Two-Factor Authentication
     */
    public function toggle2FA(Request $request)
    {
        $user = $request->user();
        
        $user->update([
            'two_factor_enabled' => !$user->two_factor_enabled
        ]);

        return response()->json([
            'message' => $user->two_factor_enabled 
                ? 'Authentification à deux facteurs activée.' 
                : 'Authentification à deux facteurs désactivée.',
            'two_factor_enabled' => $user->two_factor_enabled
        ]);
    }
}