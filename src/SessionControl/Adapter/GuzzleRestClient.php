<?php

/**
 * This file is part of the sessioncontrolpkg package.
 *
 * Drivania (c) All rights reserved.
 */

namespace Drivania\SessionControl\Adapter;

use Drivania\SessionControl\Response;
use GuzzleHttp\Client;

class GuzzleRestClient implements ClientInterface
{
    /** @var Client */
    private $client;

    /**
     * GuzzleRestClient constructor.
     */
    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * @param string $url
     * @param array  $params
     *
     * @return Response
     */
    public function post(string $url, array $params): Response
    {
        $params['http_errors'] = false;
        $responseSearch = $this->client->post($url, $params);

        $content = $responseSearch->getBody()->getContents();

        return new Response(
            $content,
            $responseSearch->getStatusCode()
        );
    }

    /**
     * @param string $url
     *
     * @return Response
     */
    public function get(string $url): Response
    {
        $responseSearch = $this->client->get($url, ['http_errors' => false]);

        $content = $responseSearch->getBody()->getContents();

        return new Response(
            $content,
            $responseSearch->getStatusCode()
        );
    }
}
