<?php
namespace App\Infrastructure\Mail;

use App\Mail\TeamInvitationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
class TeamInvitationMailer
{
    public function send(string $email, string $name, string $password, string $role, string $loginUrl): bool
    {
        try {
            Mail::to($email)->send(new TeamInvitationMail($name, $email, $password, $role, $loginUrl));
            return true;
        } catch (\Exception $e) {
            Log::warning('Invitation mail failed: ' . $e->getMessage());
            return false;
        }
    }
}