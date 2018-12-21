<?php

declare(strict_types=1);

/**
 * This file is part of the sessioncontrolpkg package.
 *
 * Drivania (c) All rights reserved.
 */

namespace Drivania\SessionControl\Exceptions;

use Drivania\SessionControl\Response;
use Throwable;

class UnknownResponseException extends SessionControlException
{
    public function __construct(Response $response, Throwable $previous = null)
    {
        $message = 'SessionControl returned ' . $response->status() .
            ' with an unknown response: ' . $response->content();

        parent::__construct($message, 1, $previous);
    }
}
