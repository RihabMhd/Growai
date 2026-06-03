<?php

namespace Infrastructure\Auth\OAuth;

use Domain\Auth\DTOs\SocialUserDTO;
use Laravel\Socialite\Facades\Socialite;

class FacebookOAuthService
{
    public function redirect(): \Symfony\Component\HttpFoundation\RedirectResponse|\Illuminate\Http\RedirectResponse
    {
        return Socialite::driver('facebook')
            ->stateless()
            ->with(['auth_type' => 'rerequest'])
            ->scopes(['public_profile', 'email'])
            ->redirect();
    }

    public function resolve(): SocialUserDTO
    {
        $facebookUser = Socialite::driver('facebook')->stateless()->user();

        return new SocialUserDTO(
            email:      $facebookUser->getEmail(),
            avatar:     $facebookUser->getAvatar(),
            provider:   'facebook',
            providerId: $facebookUser->getId(),
        );
    }
}
