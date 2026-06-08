<?php
namespace App\Application\Teams\InviteMember;

use App\Domain\Teams\{MemberRole, TeamRepositoryInterface};
use App\Infrastructure\Mail\TeamInvitationMailer;
use App\Models\User;
use Illuminate\Support\{Facades\Hash, Str};

class InviteMemberHandler
{
    public function __construct(
        private TeamRepositoryInterface  $teams,
        private TeamInvitationMailer     $mailer,
    ) {}

    public function handle(InviteMemberCommand $cmd): array
    {
        $team     = $this->teams->firstOrCreate();
        $role     = MemberRole::fromInput($cmd->role);
        $name     = $this->nameFromEmail($cmd->email);
        $password = 'Growai@' . Str::upper(Str::random(6)) . '!';

        $user = User::create([
            'team_id'             => $team->id,
            'name'                => $name,
            'email'               => $cmd->email,
            'password'            => Hash::make($password),
            'role'                => $role->value,
            'is_active'           => true,
            'quota'               => 1,
            'is_dispatch_active'  => true,
            'commission_trigger'  => 'none',
            'commission_amount'   => 0.00,
            'commission_type'     => 'fixed',
            'avatar'              => $cmd->avatar,
        ]);

        $loginUrl  = config('app.frontend_url', 'http://localhost:5173');
        $emailSent = $this->mailer->send($cmd->email, $name, $password, $role->displayName(), $loginUrl);

        $user->load('products');

        return [
            'member'     => $user,
            'email_sent' => $emailSent,
            'password'   => $emailSent ? null : $password, // expose only if mail failed
        ];
    }

    private function nameFromEmail(string $email): string
    {
        return ucwords(str_replace(['.', '_', '-'], ' ', explode('@', $email)[0]));
    }
}