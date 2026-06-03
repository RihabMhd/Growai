<?php

namespace Domain\Auth\Services;

use Domain\Auth\Services\Interfaces\TokenGeneratorInterface;

/**
 * Domain-level token generator.
 * The concrete implementation is bound in Infrastructure.
 *
 * @see \Infrastructure\Auth\ResetTokenStorage\LaravelTokenGenerator
 */
abstract class TokenGenerator implements TokenGeneratorInterface {}
