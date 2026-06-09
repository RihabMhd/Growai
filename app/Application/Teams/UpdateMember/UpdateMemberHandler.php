<?php
namespace App\Application\Teams\UpdateMember;

use App\Domain\Teams\Models\{User, MemberRole, TeamMember};

class UpdateMemberHandler
{
    public function handle(UpdateMemberCommand $cmd): TeamMember
    {
        $user = User::findOrFail($cmd->userId);

        $data = array_filter([
            'name'               => $cmd->name,
            'role'               => $cmd->role ? MemberRole::fromInput($cmd->role)->value : null,
            'is_active'          => $cmd->isActive,
            'quota'              => $cmd->quota,
            'is_dispatch_active' => $cmd->isDispatchActive,
            'commission_trigger' => $cmd->commissionTrigger,
            'commission_amount'  => $cmd->commissionAmount,
            'commission_type'    => $cmd->commissionType,
            'avatar'             => $cmd->avatar,
        ], fn($v) => $v !== null);

        $user->update($data);

        if ($cmd->productIds !== null) {
            $user->products()->sync($cmd->productIds);
        }

        $user->load('products');
        return TeamMember::fromUser($user);
    }
}