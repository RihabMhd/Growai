<?php

namespace App\Application\Teams\ImpersonateMember;

final class ImpersonateMemberCommand
{
    public function __construct(public readonly int $targetUserId) {}
}