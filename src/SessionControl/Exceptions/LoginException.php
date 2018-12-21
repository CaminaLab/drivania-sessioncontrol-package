<?php

/**
 * This file is part of the sessioncontrolpkg package.
 *
 * Drivania (c) All rights reserved.
 */

namespace Drivania\SessionControl\Exceptions;

class LoginException extends \Exception
{
    protected $code = 400;
    protected $message = 'Invalid or expired Authkey';

    public function __construct()
    {
        parent::__construct($this->message, $this->code, null);
    }
}
