<?php

/**
 * This file is part of the sessioncontrolpkg package.
 *
 * Drivania (c) All rights reserved.
 */

namespace Drivania\SessionControl;

use Drivania\SessionControl\Adapter\ClientInterface;
use Drivania\SessionControl\Exceptions\InvalidCredentialsException;
use Drivania\SessionControl\Exceptions\LoginException;
use Drivania\SessionControl\Exceptions\TokenNotUsedException;
use Drivania\SessionControl\Exceptions\UnknownResponseException;
use Drivania\SessionControl\Exceptions\UserDoesNotHavePermissionToAccessException;

/**
 * Class SessionControl.
 */
class SessionControl implements SessionControlInterface
{
    public const ADAPTER_GUZZLE = 'Guzzle';
    public const ADAPTER_TEST = 'InMemory';

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var string
     */
    private $serviceName;

    /**
     * SessionControl constructor.
     *
     * @param string      $endpoint
     * @param string      $serviceName
     * @param string|null $adapterType
     *
     * @throws \Exception
     */
    public function __construct(string $endpoint, string $serviceName, ?string $adapterType = null)
    {
        $this->endpoint = $endpoint;
        $this->serviceName = $serviceName;

        if (!$adapterType) {
            $adapterType = self::ADAPTER_GUZZLE;
        }

        $this->createAdapter($adapterType);
    }

    /**
     * @param string      $user
     * @param string      $password
     * @param bool        $remember
     * @param string|null $service
     *
     * @throws InvalidCredentialsException
     * @throws UnknownResponseException
     * @throws UserDoesNotHavePermissionToAccessException
     *
     * @return mixed
     */
    public function login(string $user, string $password, bool $remember = false, ?string $service = null)
    {
        if (!$service) {
            $service = $this->serviceName;
        }

        $request = [
            'username' => $user,
            'password' => $password,
            'service' => $service,
            'permanent' => $remember,
        ];

        $rawResponse = $this->call('login', $request);
        $response = \json_decode($rawResponse->content());

        switch ($rawResponse->status()) {
            case 200:
                return $response;

            case 401:
                throw new UserDoesNotHavePermissionToAccessException($response->error, $rawResponse->status());
            case 400:
                throw new InvalidCredentialsException($response->error, $rawResponse->status());
        }

        throw new UnknownResponseException($rawResponse);
    }

    /**
     * @param string      $token
     * @param string      $username
     * @param string|null $service
     *
     * @throws \Drivania\SessionControl\Exceptions\LoginException
     * @throws \Exception
     *
     * @return mixed
     */
    public function loginAs(string $token, string $username, ?string $service)
    {
        if (null === $service) {
            $service = $this->serviceName;
        }

        $request = [
            'token' => $token,
            'username' => $username,
            'service' => $service,
        ];

        $rawResponse = $this->call('loginas', $request);
        $response = \json_decode($rawResponse->content());

        if (empty($response->token) && !$this->isOk($rawResponse)) {
            throw new LoginException();
        }

        return $response;
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function register(string $username, string $password)
    {
        $rawResponse = $this->call('register',
            ['username' => $username, 'password' => $password]
        );

        $response = \json_decode($rawResponse->content());

        if (!$this->isOk($rawResponse)) {
            throw new \Exception('Register: Bad request, wrong sintaxis', 400);
        }

        return $response;
    }

    /**
     * @param string      $token
     * @param string      $username
     * @param string|null $service
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function grantAccess(string $token, string $username, ?string $service = null)
    {
        if (null === $service) {
            $service = $this->serviceName;
        }

        $rawResponse = $this->call('grantaccess',
            [
                'token' => $token,
                'username' => $username,
                'service' => $service,
            ]
        );

        $response = \json_decode($rawResponse->content());

        if (!$this->isOk($rawResponse)) {
            throw new \Exception('Grant access: Bad request, wrong sintaxis', 400);
        }

        return $response;
    }

    /**
     * @param string      $token
     * @param string      $username
     * @param string|null $service
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function revokeAccess(string $token, string $username, ?string $service = null)
    {
        if (null === $service) {
            $service = $this->serviceName;
        }

        $rawResponse = $this->call('revokeaccess',
            [
                'token' => $token,
                'username' => $username,
                'service' => $service,
            ]
        );

        $response = \json_decode($rawResponse->content());

        if (!$this->isOk($rawResponse)) {
            throw new \Exception($rawResponse->content()->error, 400);
        }

        return $response;
    }

    /**
     * @param string $authkey
     *
     * @throws \Drivania\SessionControl\Exceptions\LoginException
     *
     * @return mixed
     */
    public function getUser(string $authkey)
    {
        try {
            $rawResponse = $this->client->get($this->endpoint . "user?token=${authkey}");
        } catch (\Exception $exception) {
            throw new LoginException();
        }

        if (!$this->isOk($rawResponse)) {
            throw new LoginException();
        }

        $rs = $rawResponse->content();

        return @\json_decode($rs);
    }

    /**
     * @param string $username
     *
     * @throws \Exception
     *
     * @return bool|mixed
     */
    public function userAvailable(string $username)
    {
        $rawResponse = $this->call('useravailable',
            [
                'username' => $username,
            ]
        );

        $response = \json_decode($rawResponse->content());
        if (!$this->isOk($rawResponse)) {
            return false;
        }

        return $response;
    }

    /**
     * @param string[] $usernames
     *
     * @throws \Exception
     *
     * @return bool|mixed
     */
    public function usersAvailable(array $usernames)
    {
        $rawResponse = $this->call('usersavailable',
            [
                'usernames' => $usernames,
            ]
        );

        $response = \json_decode($rawResponse->content());

        if (!$this->isOk($rawResponse)) {
            return false;
        }

        return $response;
    }

    /**
     * @param string   $username
     * @param int|null $length
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function recoveryPassword(string $username, ?int $length = null)
    {
        $rawResponse = $this->call('recoverypassword',
            [
                'username' => $username,
                'length' => $length,
            ]
        );

        $response = \json_decode($rawResponse->content());

        if (!$this->isOk($rawResponse)) {
            throw new \Exception('Recovery Password: Bad request, wrong sintaxis', 400);
        }

        return $response;
    }

    /**
     * @param string $confirm_token
     * @param string $password
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function changePassword(string $confirm_token, string $password)
    {
        $rawResponse = $this->call('changepassword',
            [
                'confirm_token' => $confirm_token,
                'password' => $password,
            ]
        );

        $response = \json_decode($rawResponse->content());

        if (empty($password) || !$this->isOk($rawResponse)) {
            throw new \Exception('Change Password: Bad request, wrong sintaxis', 400);
        }

        return $response;
    }

    /**
     * @param string $token
     * @param string $username
     * @param string $new_username
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function changeUsername(string $token, string $username, string $new_username)
    {
        $rawResponse = $this->call('editusername',
            [
                'token' => $token,
                'username' => $username,
                'new_username' => $new_username,
            ]
        );

        $response = \json_decode($rawResponse->content());

        if (!$this->isOk($rawResponse)) {
            throw new \Exception('Change Username: Bad request, wrong sintaxis', 400);
        }

        return $response;
    }

    /**
     * @param string $token
     * @param string $username
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function deleteUsername(string $token, string $username)
    {
        $rawResponse = $this->call('deleteusername',
            [
                'token' => $token,
                'username' => $username,
            ]
        );

        $response = \json_decode($rawResponse->content());

        if (!$this->isOk($rawResponse)) {
            throw new \Exception('Change Username: Bad request, wrong sintaxis', 400);
        }

        return $response;
    }

    /**
     * @param string $token
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function activate(string $token)
    {
        $rawResponse = $this->call('activate',
            ['token' => $token]
        );

        $response = \json_decode($rawResponse->content());

        if (!$this->isOk($rawResponse)) {
            throw new \Exception('Change Password: Bad request, wrong sintaxis', 400);
        }

        return $response;
    }

    /**
     * @param string      $authkey
     * @param string|null $service
     * @param bool        $permanent
     *
     * @throws UnknownResponseException
     * @throws UserDoesNotHavePermissionToAccessException
     * @throws TokenNotUsedException
     *
     * @return mixed
     */
    public function validAuthKey(string $authkey, ?string $service = null, bool $permanent = false)
    {
        if (null === $service) {
            $service = $this->serviceName;
        }

        $request = [
            'token' => $authkey,
            'permanent' => $permanent,
            'service' => $service,
        ];

        $rawResponse = $this->call('validatetoken', $request);
        $response = \json_decode($rawResponse->content());

        switch ($rawResponse->status()) {
            case 200:
                return $response;
            case 403:
                throw new UserDoesNotHavePermissionToAccessException($response->error, $rawResponse->status());
            case 404:
                throw new TokenNotUsedException($response->error, $rawResponse->status());
        }

        throw new UnknownResponseException($rawResponse);
    }

    /**
     * @param string $authkey
     *
     * @throws \Drivania\SessionControl\Exceptions\LoginException
     *
     * @return mixed
     */
    public function validConfirmToken(string $authkey)
    {
        $request = [
            'token' => $authkey,
        ];

        try {
            $rawResponse = $this->call('validateconfirmtoken', $request);
        } catch (\Exception $exception) {
            throw new LoginException();
        }

        if (!$this->isOk($rawResponse)) {
            throw new LoginException();
        }

        $rs = $rawResponse->content();

        return @\json_decode($rs);
    }

    /**
     * @param string $token
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function logout(string $token)
    {
        $getResponse = $this->call('logout', [
            'token' => $token,
        ]);

        $response = \json_decode($getResponse->content());

        if (!$this->isOk($getResponse)) {
            throw new \Exception('Register error: ' . $response->error);
        }

        return $response;
    }

    /**
     * @param Response $response
     *
     * @return bool
     */
    public function isOk(Response $response): bool
    {
        return 200 === $response->status();
    }

    /**
     * @param string $clientType
     *
     * @throws \Exception
     */
    public function createAdapter(string $clientType): void
    {
        if (!\in_array($clientType, [self::ADAPTER_GUZZLE, self::ADAPTER_TEST], true)) {
            throw new \Exception('An adapter must implement the ClientInterface');
        }

        $adapterClass = '\\Drivania\\SessionControl\\Adapter\\' . $clientType . 'RestClient';
        $this->client = new $adapterClass();
    }

    /**
     * @param string     $action
     * @param array|null $iargs
     *
     * @return Response
     */
    private function call(string $action = '', ?array $iargs = null): Response
    {
        return $this->client->post($this->endpoint . $action, [
            'json' => $iargs,
        ]);
    }
}
