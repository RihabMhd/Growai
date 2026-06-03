<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Application\Auth\SocialLogin\SocialLoginCommand;
use Application\Auth\SocialLogin\SocialLoginHandler;
use Domain\Auth\Exceptions\AccountDisabledException;
use Domain\Auth\Exceptions\InvalidCredentialsException;
use Illuminate\Http\JsonResponse;
use Infrastructure\Auth\OAuth\FacebookOAuthService;
use Infrastructure\Auth\OAuth\GoogleOAuthService;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SocialAuthController extends Controller
{
    public function __construct(
        private readonly GoogleOAuthService $googleOAuth,
        private readonly FacebookOAuthService $facebookOAuth,
        private readonly SocialLoginHandler $socialLoginHandler,
    ) {}

    public function googleRedirect(): RedirectResponse|\Illuminate\Http\RedirectResponse
    {
        return $this->googleOAuth->redirect();
    }

    public function googleCallback(): JsonResponse
    {
        return $this->handleCallback(
            fn() => $this->googleOAuth->resolve()
        );
    }

    public function facebookRedirect(): RedirectResponse|\Illuminate\Http\RedirectResponse
    {
        return $this->facebookOAuth->redirect();
    }

    public function facebookCallback(): JsonResponse
    {
        return $this->handleCallback(
            fn() => $this->facebookOAuth->resolve()
        );
    }

    private function handleCallback(callable $resolve): JsonResponse
    {
        try {
            $dto    = $resolve();
            $result = $this->socialLoginHandler->handle(new SocialLoginCommand($dto));

            return response()->json($result);

        } catch (InvalidCredentialsException) {
            return response()->json([
                'message' => 'Unauthorized. Account not found. Please contact your agency administrator.',
            ], 403);

        } catch (AccountDisabledException) {
            return response()->json([
                'message' => 'Unauthorized. Your account is inactive.',
            ], 403);
        }
    }
}
