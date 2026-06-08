<?php

namespace App\Domain\Teams;

enum MemberRole: string
{
    case Admin = 'admin';
    case Staff = 'staff';

    public static function fromInput(string $input): self
    {
        return match($input) {
            'admin'          => self::Admin,
            'agent', 'staff' => self::Staff,
            default          => throw new \InvalidArgumentException("Unknown role: {$input}"),
        };
    }

    public function displayName(): string
    {
        return $this === self::Staff ? 'agent' : 'admin';
    }
}