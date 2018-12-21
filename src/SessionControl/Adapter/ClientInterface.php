<?php

/**
 * This file is part of the sessioncontrolpkg package.
 *
 * Drivania (c) All rights reserved.
 */

namespace Drivania\SessionControl\Adapter;

use Drivania\SessionControl\Response;

interface ClientInterface
{
    /**
     * @param string $url
     * @param array  $params
     *
     * @return Response
     */
    public function post(string $url, array $params): Response;

    /**
     * @param string $url
     *
     * @return Response
     */
    public function get(string $url): Response;
}
