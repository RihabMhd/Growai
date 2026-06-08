<?php
namespace App\Http\Controllers\Api;

use App\Application\Teams\InviteMember\{InviteMemberCommand, InviteMemberHandler};
use App\Application\Teams\UpdateTeamSettings\{UpdateTeamSettingsCommand, UpdateTeamSettingsHandler};
use App\Domain\Teams\WhatsAppLanguage;

class TeamController extends Controller
{
    public function storeMember(Request $request, InviteMemberHandler $handler)
    {
        $this->authorizeAdmin($request);

        $data   = $request->validate([...]);
        $result = $handler->handle(new InviteMemberCommand(
            email:  $data['email'],
            role:   $data['role'],
            avatar: $data['avatar'] ?? null,
        ));

        return response()->json([
            'message' => $result['email_sent']
                ? 'Invitation envoyée à ' . $data['email']
                : 'Membre ajouté. Mot de passe temporaire : ' . $result['password'],
            'member'  => $result['member'],
            'success' => true,
        ], 201);
    }

    private function authorizeAdmin(Request $request): void
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Non autorisé.');
        }
    }
}