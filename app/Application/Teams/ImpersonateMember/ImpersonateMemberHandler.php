<?php 

namespace App\Application\Teams\ImpersonateMember;

use App\Domain\Teams\Models\User;

class ImpersonateMemberHandler
{
    public function handle(ImpersonateMemberCommand $cmd): array
    {
        $user  = User::findOrFail($cmd->targetUserId);
        $token = $user->createToken('impersonated_token')->plainTextToken;

        return ['token' => $token, 'user' => $user];
    }
}