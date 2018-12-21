<?php

declare(strict_types=1);

/**
 * This file is part of the sessioncontrolpkg package.
 *
 * Drivania (c) All rights reserved.
 */

namespace Drivania\SessionControl\Exceptions;

use Throwable;

abstract class AbstractOperationException extends SessionControlException
{
    public function __construct(string $message, int $code, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
