<?php

namespace Domain\Auth\Services;

use Domain\Auth\Services\Interfaces\PasswordHasherInterface;

/**
 * Domain-level password hasher.
 * The concrete implementation is bound in Infrastructure.
 * This class exists so Domain code can type-hint the interface
 * without importing Infrastructure.
 *
 * @see \Infrastructure\Auth\Hashing\LaravelPasswordHasher
 */
abstract class PasswordHasher implements PasswordHasherInterface {}
