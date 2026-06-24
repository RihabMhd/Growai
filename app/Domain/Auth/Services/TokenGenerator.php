<?php

namespace Domain\Auth\Services;

use Domain\Auth\Services\Interfaces\TokenGeneratorInterface;

// bind interface in infrastructure to keep domain clean
abstract class TokenGenerator implements TokenGeneratorInterface {}
