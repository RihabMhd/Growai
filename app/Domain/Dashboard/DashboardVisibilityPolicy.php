<?php 
namespace App\Domain\Dashboard;

use App\Domain\Teams\Models\User;

final class DashboardVisibilityPolicy
{
    public function __construct(
        private readonly int  $userId,
        private readonly bool $isAgent,
    ) {}

    public static function fromUser(User $user): self
    {
        return new self($user->id, $user->isAgent());
    }

    public function isRestricted(): bool
    {
        return $this->isAgent;
    }

    public function restrictedToUserId(): ?int
    {
        return $this->isRestricted() ? $this->userId : null;
    }
}