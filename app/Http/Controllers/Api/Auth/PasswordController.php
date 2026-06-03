<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Application\Auth\ForgotPassword\ForgotPasswordCommand;
use Application\Auth\ForgotPassword\ForgotPasswordHandler;
use Application\Auth\ResetPassword\ResetPasswordCommand;
use Application\Auth\ResetPassword\ResetPasswordHandler;
use Domain\Auth\Exceptions\InvalidTokenException;
use Domain\Auth\Exceptions\TokenExpiredException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    public function __construct(
        private readonly ForgotPasswordHandler $forgotPasswordHandler,
        private readonly ResetPasswordHandler $resetPasswordHandler,
    ) {}

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $this->forgotPasswordHandler->handle(
            new ForgotPasswordCommand($request->email)
        );

        return response()->json([
            'message' => 'If that email exists, a reset link has been sent.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'token'    => ['required'],
            'password' => ['required', 'min:6', 'confirmed'],
        ]);

        try {
            $this->resetPasswordHandler->handle(
                new ResetPasswordCommand($data['email'], $data['token'], $data['password'])
            );

            return response()->json(['message' => 'Password reset successful']);

        } catch (InvalidTokenException $e) {
            return response()->json(['message' => $e->getMessage()], 400);

        } catch (TokenExpiredException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
