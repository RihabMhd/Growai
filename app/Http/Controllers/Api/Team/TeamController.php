<?php

namespace App\Http\Controllers\Api\Team;

use App\Http\Controllers\Controller;
use Illuminate\Http\{Request, JsonResponse};
use App\Application\Teams\ListTeamMembers\{ListTeamMembersHandler, ListTeamMembersQuery};
use App\Application\Teams\InviteMember\{InviteMemberCommand, InviteMemberHandler};
use App\Application\Teams\UpdateMember\{UpdateMemberCommand, UpdateMemberHandler};
use App\Application\Teams\DeleteMember\{DeleteMemberCommand, DeleteMemberHandler};
use App\Application\Teams\ImpersonateMember\{ImpersonateMemberCommand, ImpersonateMemberHandler};
use App\Application\Teams\UpdateTeamSettings\{UpdateTeamSettingsCommand, UpdateTeamSettingsHandler};
use App\Http\Requests\InviteMemberRequest;
use App\Http\Requests\Teams\UpdateMemberRequest;

class TeamController extends Controller
{
    public function __construct(
        private ListTeamMembersHandler    $listHandler,
        private InviteMemberHandler       $inviteHandler,
        private UpdateMemberHandler       $updateHandler,
        private DeleteMemberHandler       $deleteHandler,
        private ImpersonateMemberHandler  $impersonateHandler,
        private UpdateTeamSettingsHandler $settingsHandler,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->listHandler->handle());
    }

    public function storeMember(InviteMemberRequest $request): JsonResponse
    {
        $result = $this->inviteHandler->handle(new InviteMemberCommand(
            email: $request->email,
            role: $request->role,
            avatar: $request->avatar,
        ));

        $message = $result['email_sent']
            ? "Membre ajouté. Invitation envoyée à {$request->email}."
            : "Membre ajouté. SMTP non configuré. Mot de passe : {$result['temp_password']}";

        return response()->json(['message' => $message, 'member' => $result['member'], 'success' => true], 201);
    }

    public function updateMember(UpdateMemberRequest $request, int $id): JsonResponse
    {
        $member = $this->updateHandler->handle(new UpdateMemberCommand(
            userId: $id,
            name: $request->name,
            role: $request->role,
            isActive: $request->is_active,
            quota: $request->quota,
            isDispatchActive: $request->is_dispatch_active,
            commissionTrigger: $request->commission_trigger,
            commissionAmount: $request->commission_amount,
            commissionType: $request->commission_type,
            avatar: $request->avatar,
            productIds: $request->product_ids,
        ));

        return response()->json(['message' => 'Membre mis à jour.', 'member' => $member]);
    }

    public function impersonate(Request $request, int $id): JsonResponse
    {
        $result = $this->impersonateHandler->handle(new ImpersonateMemberCommand($id));

        return response()->json([
            'message' => 'Connexion simulée réussie en tant que ' . $result['user']->name,
            'token'   => $result['token'],
            'user'    => $result['user'],
        ]);
    }

    public function destroyMember(Request $request, int $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteMemberCommand($id));
        return response()->json(['message' => 'Membre supprimé avec succès.']);
    }

    public function settings(): JsonResponse
    {
        $result = $this->listHandler->handle();
        return response()->json(['whatsapp_language' => $result['team']->whatsapp_language]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'whatsapp_language'   => ['nullable', 'string', 'in:FR,AR,FR/AR,Darija AR,Darija FR'],
            'dispatch_auto'       => ['nullable', 'boolean'],
            'inactive_strategy'   => ['nullable', 'string', 'in:do_nothing,reassign,deactivate'],
            'commission_currency' => ['nullable', 'string', 'max:20'],
        ]);

        $this->settingsHandler->handle(new UpdateTeamSettingsCommand(
            whatsappLanguage: $validated['whatsapp_language']   ?? null,
            dispatchAuto: $validated['dispatch_auto']       ?? null,
            inactiveStrategy: $validated['inactive_strategy']   ?? null,
            commissionCurrency: $validated['commission_currency'] ?? null,
        ));

        $team = app(\App\Domain\Teams\TeamRepositoryInterface::class)->getOrCreateDefault();

        return response()->json(['success' => true, 'team' => $team]);
    }
}
