<?php 

namespace App\Application\Teams\DeleteMember;

use App\Domain\Teams\Models\User;
use Illuminate\Validation\ValidationException;

class DeleteMemberHandler
{
    public function handle(DeleteMemberCommand $cmd): void
    {
        $user = User::findOrFail($cmd->userId);

        if ($user->role->value === 'admin' && User::where('role', 'admin')->count() <= 1) {
            throw ValidationException::withMessages([
                'user' => 'Impossible de supprimer le dernier administrateur !'
            ]);
        }

        $user->delete();
    }
}