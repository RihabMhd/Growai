<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Login user
     */
    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {

            return [
                'success' => false,
                'status' => 401,
                'message' => 'Invalid credentials'
            ];
        }

        if (!$user->is_active) {

            return [
                'success' => false,
                'status' => 403,
                'message' => 'User inactive'
            ];
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->load('products');

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Login successful',

            'data' => [
                'token' => $token,
                'user' => $user
            ]
        ];
    }

    /**
     * Logout user
     */
    public function logout($user): array
    {
        $user->currentAccessToken()->delete();

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Logged out successfully'
        ];
    }
}