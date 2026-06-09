<?php 

namespace App\Application\Teams\DeleteMember;

final class DeleteMemberCommand
{
    public function __construct(public readonly int $userId) {}
}