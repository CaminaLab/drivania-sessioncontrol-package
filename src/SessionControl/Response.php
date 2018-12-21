<?php

/**
 * This file is part of the sessioncontrolpkg package.
 *
 * Drivania (c) All rights reserved.
 */

namespace Drivania\SessionControl;

class Response
{
    /** @var mixed */
    private $content;

    /** @var int */
    private $status;

    /**
     * Response constructor.
     *
     * @param mixed $content
     * @param int   $status
     */
    public function __construct($content, int $status)
    {
        $this->content = $content;
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function content()
    {
        return $this->content;
    }

    /**
     * @return int
     */
    public function status(): int
    {
        return $this->status;
    }
}
