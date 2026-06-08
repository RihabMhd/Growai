<?php

namespace App\Domain\Teams;

final class TeamMember
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $name,
        public readonly string  $email,
        public readonly string  $role,
        public readonly string  $roleDisplay,
        public readonly bool    $isActive,
        public readonly bool    $isDispatchActive,
        public readonly int     $quota,
        public readonly float   $commissionAmount,
        public readonly string  $commissionType,
        public readonly string  $commissionTrigger,
        public readonly ?string $avatar,
        public readonly ?string $avatarUrl,
        public readonly ?string $whatsapp,
        public readonly array   $products,
    ) {}

    public static function fromUser(\App\Domain\Teams\Models\User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            role: $user->role->value,         
            roleDisplay: $user->role->displayName(),
            isActive: $user->is_active,
            isDispatchActive: $user->is_dispatch_active,
            quota: $user->quota,
            commissionAmount: $user->commission_amount,
            commissionType: $user->commission_type,
            commissionTrigger: $user->commission_trigger,
            avatar: $user->avatar,
            avatarUrl: $user->avatar_url,
            whatsapp: $user->whatsapp,
            products: $user->relationLoaded('products')
                ? $user->products->toArray()
                : [],
        );
    }
}
