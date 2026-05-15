<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function googleRedirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function googleCallback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::where('email', $googleUser->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized. Account not found. Please contact your agency administrator.'
            ], 403);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Unauthorized. Your account is inactive.'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }

public function facebookRedirect()
{
    return Socialite::driver('facebook')
        ->stateless()
        ->with(['auth_type' => 'rerequest'])
        ->scopes(['public_profile', 'email'])
        ->redirect();
}

    public function facebookCallback()
    {
        $facebookUser = Socialite::driver('facebook')->stateless()->user();

        $user = User::where('email', $facebookUser->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized. Account not found. Please contact your agency administrator.'
            ], 403);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Unauthorized. Your account is inactive.'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }
}
