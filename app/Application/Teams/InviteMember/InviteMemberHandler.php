<?php
namespace App\Application\Teams\InviteMember;

use App\Domain\Teams\Models\{User, MemberRole};
use App\Infrastructure\Mail\TeamInvitationMailerInterface;
use Illuminate\Support\{Str, Facades\Hash};

class InviteMemberHandler
{
    public function __construct(
        private TeamInvitationMailerInterface $mailer,
    ) {}

    public function handle(InviteMemberCommand $cmd): array
    {
        $role        = MemberRole::fromInput($cmd->role);
        $name        = ucwords(str_replace(['.','_','-'], ' ', explode('@', $cmd->email)[0]));
        $tempPassword= 'Growai@' . Str::upper(Str::random(6)) . '!';

        $user = User::create([
            'team_id'            => 1,
            'name'               => $name,
            'email'              => $cmd->email,
            'password'           => Hash::make($tempPassword),
            'role'               => $role->value,
            'is_active'          => true,
            'quota'              => 1,
            'is_dispatch_active' => true,
            'commission_trigger' => 'none',
            'commission_amount'  => 0.00,
            'commission_type'    => 'fixed',
            'avatar'             => $cmd->avatar,
        ]);

        $user->load('products');

        $emailSent = $this->mailer->send(
            $cmd->email, $name, $tempPassword,
            $role->displayName(),
            config('app.frontend_url', 'http://localhost:5173')
        );

        return [
            'member'     => \App\Domain\Teams\Models\TeamMember::fromUser($user),
            'email_sent' => $emailSent,
            'temp_password' => $emailSent ? null : $tempPassword,
        ];
    }
}