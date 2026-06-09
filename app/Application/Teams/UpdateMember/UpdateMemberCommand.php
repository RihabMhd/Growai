<?php

namespace App\Application\Teams\UpdateMember;

final class UpdateMemberCommand
{
    public function __construct(
        public readonly int      $userId,
        public readonly ?string  $name               = null,
        public readonly ?string  $role               = null,
        public readonly ?bool    $isActive           = null,
        public readonly ?int     $quota              = null,
        public readonly ?bool    $isDispatchActive   = null,
        public readonly ?string  $commissionTrigger  = null,
        public readonly ?float   $commissionAmount   = null,
        public readonly ?string  $commissionType     = null,
        public readonly ?string  $avatar             = null,
        public readonly ?array   $productIds         = null,
    ) {}
}