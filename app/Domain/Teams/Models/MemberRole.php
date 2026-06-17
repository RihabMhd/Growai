<?php

namespace App\Domain\Teams\Models;

enum MemberRole: string
{
    case Admin = 'admin';
    case Staff = 'staff';

    public static function fromInput(string $input): self
    {
        return match ($input) {
            'admin'          => self::Admin,
            'agent', 'staff' => self::Staff,
            default          => throw new \InvalidArgumentException("Unknown role: {$input}"),
        };
    }

    public function displayName(): string
    {
        return $this === self::Staff ? 'agent' : 'admin';
    }

    public function isStaff(): bool
    {
        return $this === self::Staff;
    }
    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}
