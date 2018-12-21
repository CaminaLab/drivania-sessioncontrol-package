<?php

/**
 * This file is part of the sessioncontrolpkg package.
 *
 * Drivania (c) All rights reserved.
 */

namespace Drivania\SessionControl;

interface SessionControlInterface
{
    /**
     * @param string $user
     * @param string $password
     * @param bool   $remember
     * @param string $service
     *
     * @return mixed
     */
    public function login(string $user, string $password, bool $remember, string $service);

    /**
     * @param string $token
     * @param string $username
     * @param string $service
     *
     * @return mixed
     */
    public function loginAs(string $token, string $username, string $service);

    /**
     * @param string $username
     * @param string $password
     *
     * @return mixed
     */
    public function register(string $username, string $password);

    /**
     * @param string $token
     * @param string $username
     * @param string $service
     *
     * @return mixed
     */
    public function grantAccess(string $token, string $username, string $service);

    /**
     * @param string $token
     * @param string $username
     * @param string $service
     *
     * @return mixed
     */
    public function revokeAccess(string $token, string $username, string $service);

    /**
     * @param string $username
     *
     * @return mixed
     */
    public function userAvailable(string $username);

    /**
     * @param string[] $usernames
     *
     * @throws \Exception
     *
     * @return bool|mixed
     */
    public function usersAvailable(array $usernames);

    /**
     * @param string $username
     *
     * @return mixed
     */
    public function recoveryPassword(string $username);

    /**
     * @param string $confirm_token
     * @param string $password
     *
     * @return mixed
     */
    public function changePassword(string $confirm_token, string $password);

    /**
     * @param string $token
     * @param string $username
     * @param string $new_username
     *
     * @return mixed
     */
    public function changeUsername(string $token, string $username, string $new_username);

    /**
     * @param string $token
     * @param string $username
     *
     * @return mixed
     */
    public function deleteUsername(string $token, string $username);

    /**
     * @param string $token
     *
     * @return mixed
     */
    public function activate(string $token);

    /**
     * @param string $authkey
     * @param bool   $permanent
     * @param string $service
     *
     * @return mixed
     */
    public function validAuthKey(string $authkey, string $service, bool $permanent);

    /**
     * @param string $token
     *
     * @return mixed
     */
    public function validConfirmToken(string $token);

    /**
     * @param string $token
     *
     * @return mixed
     */
    public function logout(string $token);
}
