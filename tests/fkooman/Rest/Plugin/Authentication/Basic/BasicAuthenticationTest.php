<?php

/**
 * Copyright 2014 François Kooman <fkooman@tuxed.net>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace fkooman\Rest\Plugin\Authentication\Basic;

use fkooman\Http\Request;
use PHPUnit_Framework_TestCase;

class BasicAuthenticationTest extends PHPUnit_Framework_TestCase
{
    public function testBasicAuthCorrect()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => '',
            'REQUEST_URI' => '/',
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_METHOD' => 'GET',
            'HTTP_AUTHORIZATION' => sprintf('Basic %s', base64_encode('user:pass')),
        );
        $request = new Request($srv);
        $basicAuth = new BasicAuthentication(
            function ($userId) {
                return password_hash('pass', PASSWORD_DEFAULT);
            },
            array('realm' => 'realm')
        );
        $userInfo = $basicAuth->execute($request, array());
        $this->assertEquals('user', $userInfo->getUserId());
    }

    /**
     * @expectedException fkooman\Http\Exception\UnauthorizedException
     * @expectedExceptionMessage invalid_credentials
     */
    public function testBasicAuthFailExplicitRequireAuth()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => '',
            'REQUEST_URI' => '/',
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_METHOD' => 'GET',
            'HTTP_AUTHORIZATION' => sprintf('Basic %s', base64_encode('user:pazz')),
        );
        $request = new Request($srv);
        $basicAuth = new BasicAuthentication(
            function ($userId) {
                return password_hash('pass', PASSWORD_DEFAULT);
            },
            array('realm' => 'realm')
        );
        $userInfo = $basicAuth->execute($request, array('requireAuth' => true));
        $this->assertEquals('user', $userInfo->getUserId());
    }

    /**
     * @expectedException fkooman\Http\Exception\UnauthorizedException
     * @expectedExceptionMessage invalid_credentials
     */
    public function testBasicAuthWrongUser()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => '',
            'REQUEST_URI' => '/',
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_METHOD' => 'GET',
            'HTTP_AUTHORIZATION' => sprintf('Basic %s', base64_encode('wronguser:pass')),
        );
        $request = new Request($srv);
        $basicAuth = new BasicAuthentication(
            function ($userId) {
                // we simulate not finding the userId 'wronguser'
                return false;
            },
            array('realm' => 'realm')
        );
        $basicAuth->execute($request, array());
    }

    /**
     * @expectedException fkooman\Http\Exception\UnauthorizedException
     * @expectedExceptionMessage invalid_credentials
     */
    public function testBasicAuthWrongPass()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => '',
            'REQUEST_URI' => '/',
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_METHOD' => 'GET',
            'HTTP_AUTHORIZATION' => sprintf('Basic %s', base64_encode('user:wrongpass')),
        );
        $request = new Request($srv);
        $basicAuth = new BasicAuthentication(
            function ($userId) {
                return 'someWrongHashValue';
            },
            array('realm' => 'realm')
        );
        $basicAuth->execute($request, array());
    }

    /**
     * @expectedException fkooman\Http\Exception\UnauthorizedException
     * @expectedExceptionMessage no_credentials
     */
    public function testNoAuth()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => '',
            'REQUEST_URI' => '/',
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_METHOD' => 'GET',
        );
        $request = new Request($srv);
        $basicAuth = new BasicAuthentication(
            function ($userId) {
                return 'whatever';
            },
            array('realm' => 'realm')
        );
        $basicAuth->execute($request, array());
    }

    public function testOptionalAuthNoCredentials()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => '',
            'REQUEST_URI' => '/',
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_METHOD' => 'GET',
        );
        $request = new Request($srv);
        $basicAuth = new BasicAuthentication(
            function ($userId) {
                return 'someWrongHashValue';
            },
            array('realm' => 'realm')
        );
        $this->assertNull($basicAuth->execute($request, array('requireAuth' => false)));
    }

    public function testOptionalAuthCorrect()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => '',
            'REQUEST_URI' => '/',
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_METHOD' => 'GET',
            'HTTP_AUTHORIZATION' => sprintf('Basic %s', base64_encode('user:pass')),
        );
        $request = new Request($srv);
        $basicAuth = new BasicAuthentication(
            function ($userId) {
                return password_hash('pass', PASSWORD_DEFAULT);
            },
            array('realm' => 'realm')
        );
        $userInfo = $basicAuth->execute($request, array('requireAuth' => false));
        $this->assertEquals('user', $userInfo->getUserId());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage provided parameter is not callable
     */
    public function testUncallableParameter()
    {
        new BasicAuthentication('foo');
    }

    public function testGetScheme()
    {
        $basicAuth = new BasicAuthentication(
            function ($userId) {
                'xyz';
            },
            array('realm' => 'realm')
        );
        $this->assertEquals('Basic', $basicAuth->getScheme());
    }

    public function testExtractUserPass()
    {
        $this->assertFalse(BasicAuthentication::extractUserPass(''));
        $this->assertFalse(BasicAuthentication::extractUserPass(','));
        $this->assertFalse(BasicAuthentication::extractUserPass(base64_encode('foo')));
        $this->assertEquals(
            array('foo', 'bar'),
            BasicAuthentication::extractUserPass(
                base64_encode('foo:bar')
            )
        );
    }
}
