<?php

/**
 * This file is part of the sessioncontrolpkg package.
 *
 * Drivania (c) All rights reserved.
 */

namespace Drivania\SessionControl\Adapter;

use Drivania\SessionControl\Response;

class InMemoryRestClient implements ClientInterface
{
    public const TOKEN = '8e3e25ec354b736297d75b160a34ed9f';

    public const USERNAME_VALID = 'username';
    public const USERNAME_NOTALLOWED = 'notalloweduser';

    /**
     * @param string $url
     * @param array  $params
     *
     * @return Response
     */
    public function post(string $url, array $params): Response
    {
        $content = '';
        $responseCode = 0;

        if ('login' === $url) {
            if ('validServiceName' !== $params['json']['service']) {
                $responseCode = 400;
                $content = '{"error": "This service name are invalid (' .
                    $params['json']['username'] . ' | ' . $params['json']['service'] . ')"}';
            } elseif (static::USERNAME_VALID === $params['json']['username']) {
                if ('password' === $params['json']['password']) {
                    $responseCode = 200;
                    $content = \json_encode([
                        'token' => static::TOKEN,
                        'token_ttl' => '2018-12-21T12:03:00+0000',
                        'username' => static::USERNAME_VALID,
                    ]);
                } else {
                    $responseCode = 400;
                    $content = '{"error": "Wrong password.(' . static::USERNAME_VALID . ')"}';
                }
            } elseif (
                static::USERNAME_NOTALLOWED === $params['json']['username']
                && 'password2' === $params['json']['password']
            ) {
                $responseCode = 401;
                $content = '{"error": "You dont have permissions to access. ('
                    . $params['json']['username'] . ' | ' . $params['json']['service'] . ')"}';
            } else {
                $responseCode = 400;
                $content = '{"error": "User not found.(' . $params['json']['username'] . ')"}';
            }

            return new Response($content, $responseCode);
        }

        if ('validatetoken' === $url) {
            if (self::TOKEN === $params['json']['token']) {
                $responseCode = 200;
                $content = '{"auth_key": "' . self::TOKEN .
                    '","token_ttl": "2017-04-18T08:48:38+0000", "username": "username"}';
            } elseif ('INVALIDTOKEN' === $params['json']['token']) {
                $responseCode = 404;
                $content = '{"error": "User not found.()"}';
            } elseif ('TOKENWITHOUTSERVICE' === $params['json']['token']) {
                $responseCode = 403;
                $content = '{ "error": "You dont have permissions to access. (username | '
                    . $params['json']['service'] . ')"}';
            }

            return new Response($content, $responseCode);
        }

        if ('register' === $url) {
            if ('newusername' === $params['json']['username']) {
                $content = '{"confirm_token": "' . self::TOKEN . '"}';
                $responseCode = 200;
            } elseif ('username' === $params['json']['username']) {
                $content = '{ "error": "You dont have permissions to access."}';
                $responseCode = 500;
            }

            return new Response($content, $responseCode);
        }

        if ('grantaccess' === $url) {
            if ('username' === $params['json']['username'] && $params['json']['token'] === '' . self::TOKEN . '') {
                $content = '{}';
                $responseCode = 200;
            } else {
                $content = '{ "error": "User not found."}';
                $responseCode = 500;
            }

            return new Response($content, $responseCode);
        }

        if ('useravailable' === $url) {
            if ('otheruser' === $params['json']['username']) {
                $content = '{ "success": true}';

                return new Response($content, 200);
            }

            $content = '{"success": false}';

            return new Response($content, 406);
        }

        if ('usersavailable' === $url) {
            $result = [];

            foreach ($params['json']['usernames'] as $username) {
                $result[$username] = 'otheruser' === $username;
            }

            return new Response(\json_encode($result), 200);
        }

        if ('recoverypassword' === $url) {
            if ('username' === $params['json']['username']) {
                if ($params['json']['length']) {
                    $token = \mb_substr(self::TOKEN, 0, $params['json']['length']);
                } else {
                    $token = self::TOKEN;
                }

                $content = '{"confirm_token": "' . $token . '"}';

                return new Response($content, 200);
            }

            $content = '{"error": "User not found."}';

            return new Response($content, 500);
        }

        if ('changepassword' === $url) {
            if ($params['json']['confirm_token'] === '' . self::TOKEN . '') {
                $content = '{}';

                return new Response($content, 200);
            }

            $content = '{"error": "User not found."}';

            return new Response($content, 500);
        }

        if ('activate' === $url) {
            if ($params['json']['token'] === '' . self::TOKEN . '') {
                $content = '{"active": true}';

                return new Response($content, 200);
            }

            $content = '{"error": "Invalid confirmation token"}';

            return new Response($content, 500);
        }

        throw new \RuntimeException('URL not recognized');
    }

    /**
     * @param string $url
     *
     * @return Response
     */
    public function get(string $url): Response
    {
        $parts = \parse_url($url);
        \parse_str($parts['query'], $params);

        if (false !== \mb_strpos($url, 'user?token=')) {
            if (self::TOKEN === $params['token']) {
                $content = '{"username": "username"}';

                return new Response($content, 200);
            }

            if ('INVALIDTOKEN' === $params['token']) {
                $content = '{"error": "User not found."}';

                return new Response($content, 500);
            }

            if ('TOKENWITHOUTSERVICE' === $params['token']) {
                $content = '{ "error": "You dont have permissions to access."}';

                return new Response($content, 500);
            }
        }

        $content = '{"error": "User not found."}';

        return new Response($content, 500);
    }
}
