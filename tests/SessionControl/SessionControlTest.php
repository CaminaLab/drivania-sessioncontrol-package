<?php

/**
 * This file is part of the sessioncontrolpkg package.
 *
 * Drivania (c) All rights reserved.
 */

namespace Tests\Drivania\SessionControl;

use Drivania\SessionControl\Adapter\GuzzleRestClient;
use Drivania\SessionControl\SessionControl;
use PHPUnit\Framework\TestCase;

class SessionControlTest extends TestCase
{
    private $obj;

    protected function setUp()
    {
        $this->obj = new SessionControl('', 'validServiceName', 'InMemory');
    }

    public function testDefaultAdapter(): void
    {
        $obj = new SessionControl('', 'validServiceName');

        $reflection = new \ReflectionObject($obj);
        $property = $reflection->getProperty('client');

        $property->setAccessible(true);
        $testObj = $property->getValue($obj);

        $this->assertInstanceOf(GuzzleRestClient::class, $testObj);
    }

    // --- LOGIN -------------------------------------------------------------------------------------------------------

    /**
     * @expectedException \Drivania\SessionControl\Exceptions\InvalidCredentialsException
     */
    public function testLoginInvalidServiceName(): void
    {
        $this->obj->login('username', 'password', false, 'invalidServiceName');
    }

    /**
     * @expectedException \Drivania\SessionControl\Exceptions\InvalidCredentialsException
     */
    public function testLoginWrongUser(): void
    {
        $this->obj->login('wronguser', 'wrongpassword');
    }

    /**
     * @expectedException \Drivania\SessionControl\Exceptions\InvalidCredentialsException
     */
    public function testLoginWrongPassword(): void
    {
        $this->obj->login('username', 'wrongpassword');
    }

    /**
     * @expectedException \Drivania\SessionControl\Exceptions\UserDoesNotHavePermissionToAccessException
     */
    public function testLoginWithoutPermissions(): void
    {
        $this->obj->login('notalloweduser', 'password2');
    }

    public function testLoginOK(): void
    {
        $token = $this->obj->login('username', 'password');
        $this->assertSame('8e3e25ec354b736297d75b160a34ed9f', $token->token);
    }

    // --- VALID AUTH KEY ----------------------------------------------------------------------------------------------

    /**
     * @throws \Drivania\SessionControl\Exceptions\TokenNotUsedException
     * @throws \Drivania\SessionControl\Exceptions\UnknownResponseException
     * @throws \Drivania\SessionControl\Exceptions\UserDoesNotHavePermissionToAccessException
     */
    public function testValidAuthKeyOK(): void
    {
        $token = $this->obj->validAuthKey('8e3e25ec354b736297d75b160a34ed9f');
        $this->assertSame('8e3e25ec354b736297d75b160a34ed9f', $token->auth_key);
    }

    /**
     * @expectedException \Drivania\SessionControl\Exceptions\TokenNotUsedException
     */
    public function testInvalidAuthKey(): void
    {
        $this->obj->validAuthKey('INVALIDTOKEN');
    }

    /**
     * @expectedException \Drivania\SessionControl\Exceptions\UserDoesNotHavePermissionToAccessException
     */
    public function testValidAuthKeyWithoutPermission(): void
    {
        $this->obj->validAuthKey('TOKENWITHOUTSERVICE');
    }

    // --- REGISTER ----------------------------------------------------------------------------------------------------

    public function testRegisterUserOK(): void
    {
        $token = $this->obj->register('newusername', 'newpassword');
        $this->assertSame('8e3e25ec354b736297d75b160a34ed9f', $token->confirm_token);
    }

    public function testRegisterUserAlreadyExists(): void
    {
        $this->expectException(\Exception::class);
        $this->obj->register('username', 'password');
    }

    // --- GRANT ACCESS ------------------------------------------------------------------------------------------------

    public function testGrantAccessOK(): void
    {
        $token = $this->obj->grantAccess('8e3e25ec354b736297d75b160a34ed9f', 'username', 'booking');
        $this->assertSame(\stdClass::class, \get_class($token));
    }

    public function testGrantAccessKO(): void
    {
        $this->expectException(\Exception::class);
        $token = $this->obj->grantAccess('INVALIDTOKEN', 'username', 'booking');
    }

    // --- USER AVAILABLE ----------------------------------------------------------------------------------------------

    public function testUserAvailableOk(): void
    {
        $response = $this->obj->userAvailable('otheruser');
        $this->assertTrue($response->success);
    }

    public function testUserAvailableKO(): void
    {
        $response = $this->obj->userAvailable('username');
        $this->assertFalse($response);
    }

    public function testUsersAvailable(): void
    {
        $response = $this->obj->usersAvailable(['otheruser', 'username']);
        $this->assertEquals(['otheruser' => true, 'username' => false], (array) $response);
    }

    // --- RECOVERY PASSWORD -------------------------------------------------------------------------------------------

    public function testRecoveryPasswordOk(): void
    {
        $response = $this->obj->recoveryPassword('username');
        $this->assertSame('8e3e25ec354b736297d75b160a34ed9f', $response->confirm_token);
    }

    public function testRecoveryPasswordLengthOk(): void
    {
        $response = $this->obj->recoveryPassword('username', 5);
        $this->assertSame('8e3e2', $response->confirm_token);
    }

    public function testRecoveryPasswordKO(): void
    {
        $this->expectException(\Exception::class);
        $response = $this->obj->recoveryPassword('otherusername');
    }

    // --- CHANGE PASSWORD ---------------------------------------------------------------------------------------------

    public function testChangePasswordOk(): void
    {
        $response = $this->obj->changePassword('8e3e25ec354b736297d75b160a34ed9f', 'newpassword');

        $this->assertSame(\stdClass::class, \get_class($response));
    }

    public function testChangePasswordKO(): void
    {
        $this->expectException(\Exception::class);
        $this->obj->changePassword('INVALIDTOKEN', 'newpassword');
    }

    // --- ACTIVATE ----------------------------------------------------------------------------------------------------

    public function testActivateUserOk(): void
    {
        $response = $this->obj->activate('8e3e25ec354b736297d75b160a34ed9f');

        $this->assertTrue($response->active);
    }

    public function testActivateUserKO(): void
    {
        $this->expectException(\Exception::class);
        $this->obj->activate('INVALIDTOKEN');
    }
}
