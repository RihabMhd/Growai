<?php

namespace Domain\Auth\Services\Interfaces;

interface TokenGeneratorInterface
{
    /**
     * Generate a cryptographically secure plain-text token.
     */
    public function generate(): string;

    /**
     * Hash the plain-text token for safe storage.
     */
    public function hash(string $plain): string;
}