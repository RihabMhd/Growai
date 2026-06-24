<?php

namespace Domain\Auth\Services;

use Domain\Auth\Services\Interfaces\PasswordHasherInterface;

// bind interface in infrastructure to keep domain clean
abstract class PasswordHasher implements PasswordHasherInterface {}
