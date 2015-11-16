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
        $request = new Request(
            array(
                'SERVER_NAME' => 'www.example.org',
                'SERVER_PORT' => 80,
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/',
                'SCRIPT_NAME' => '/index.php',
                'REQUEST_METHOD' => 'GET',
                'HTTP_AUTHORIZATION' => sprintf('Basic %s', base64_encode('user:pass')),
            )
        );
        $basicAuth = new BasicAuthentication(
            function ($userId) {
                return '$2y$10$XwlqKgPF.OJvaZxxCXO3hOi5wSh0WbLq9quN/319SVEFl5YWyv3WC';
            }
        );
        $userInfo = $basicAuth->isAuthenticated($request);
        $this->assertEquals('user', $userInfo->getUserId());
    }

    /**
     * @expectedException fkooman\Http\Exception\UnauthorizedException
     * @expectedExceptionMessage invalid_credentials
     */
    public function testBasicAuthInvalidPass()
    {
        $request = new Request(
            array(
                'SERVER_NAME' => 'www.example.org',
                'SERVER_PORT' => 80,
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/',
                'SCRIPT_NAME' => '/index.php',
                'REQUEST_METHOD' => 'GET',
                'HTTP_AUTHORIZATION' => sprintf('Basic %s', base64_encode('user:pazz')),
            )
        );
        $basicAuth = new BasicAuthentication(
            function ($userId) {
                return '$2y$10$XwlqKgPF.OJvaZxxCXO3hOi5wSh0WbLq9quN/319SVEFl5YWyv3WC';
            }
        );
        $this->assertFalse($basicAuth->isAuthenticated($request));
        $basicAuth->requestAuthentication($request);
    }

    /**
     * @expectedException fkooman\Http\Exception\UnauthorizedException
     * @expectedExceptionMessage invalid_credentials
     */
    public function testBasicAuthInvalidUser()
    {
        $request = new Request(
            array(
                'SERVER_NAME' => 'www.example.org',
                'SERVER_PORT' => 80,
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/',
                'SCRIPT_NAME' => '/index.php',
                'REQUEST_METHOD' => 'GET',
                'HTTP_AUTHORIZATION' => sprintf('Basic %s', base64_encode('user:pass')),
            )
        );
        $basicAuth = new BasicAuthentication(
            function ($userId) {
                return false;
            }
        );
        $this->assertFalse($basicAuth->isAuthenticated($request));
        $basicAuth->requestAuthentication($request);
    }

    /**
     * @expectedException fkooman\Http\Exception\UnauthorizedException
     * @expectedExceptionMessage no_credentials
     */
    public function testNoAttempt()
    {
        $request = new Request(
            array(
                'SERVER_NAME' => 'www.example.org',
                'SERVER_PORT' => 80,
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/',
                'SCRIPT_NAME' => '/index.php',
                'REQUEST_METHOD' => 'GET',
            )
        );
        $basicAuth = new BasicAuthentication(
            function ($userId) {
                return '$2y$10$XwlqKgPF.OJvaZxxCXO3hOi5wSh0WbLq9quN/319SVEFl5YWyv3WC';
            }
        );
        $this->assertFalse($basicAuth->isAuthenticated($request));
        $basicAuth->requestAuthentication($request);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage argument must be callable
     */
    public function testNonCallableParameter()
    {
        new BasicAuthentication('foo');
    }
}
