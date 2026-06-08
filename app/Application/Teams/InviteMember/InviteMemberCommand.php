<?php
namespace App\Application\Teams\InviteMember;

final class InviteMemberCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $role,   // raw input; handler normalizes via MemberRole enum
        public readonly ?string $avatar,
    ) {}
}