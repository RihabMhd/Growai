<?php

namespace Domain\Auth\Exceptions;

use Exception;

class TokenExpiredException extends Exception
{
    public function __construct()
    {
        parent::__construct('Token has expired.', 400);
    }
}