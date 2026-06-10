<?php
namespace App\Application\Teams\InviteMember;

final class InviteMemberCommand
{
    public function __construct(
        public readonly string  $email,
        public readonly string  $role,   
        public readonly ?string $avatar,
    ) {}
}