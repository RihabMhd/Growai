<?php 
namespace App\Infrastructure\Mail;

interface TeamInvitationMailerInterface
{
    public function send(string $email, string $name, string $password, string $role, string $loginUrl): bool;
}