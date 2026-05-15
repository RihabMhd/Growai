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
        return response()->json([
            'user' => $request->user()
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
}