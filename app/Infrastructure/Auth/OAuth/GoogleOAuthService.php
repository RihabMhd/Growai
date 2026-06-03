<?php

namespace Infrastructure\Auth\OAuth;

use Domain\Auth\DTOs\SocialUserDTO;
use Laravel\Socialite\Facades\Socialite;

class GoogleOAuthService
{
    public function redirect(): \Symfony\Component\HttpFoundation\RedirectResponse|\Illuminate\Http\RedirectResponse
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function resolve(): SocialUserDTO
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        return new SocialUserDTO(
            email:      $googleUser->getEmail(),
            avatar:     $googleUser->getAvatar(),
            provider:   'google',
            providerId: $googleUser->getId(),
        );
    }
}
