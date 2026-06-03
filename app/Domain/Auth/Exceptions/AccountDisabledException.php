<?php

namespace Domain\Auth\Exceptions;

use Exception;

class AccountDisabledException extends Exception
{
    public function __construct()
    {
        parent::__construct('Your account is inactive.', 403);
    }
}