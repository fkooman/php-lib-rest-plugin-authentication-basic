<?php

/**
* Copyright 2014 François Kooman <fkooman@tuxed.net>
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

namespace fkooman\Rest;

use fkooman\Http\Request;
use fkooman\Rest\Plugin\Basic\BasicAuthentication;
use PHPUnit_Framework_TestCase;

class BasicAuthenticationTest extends PHPUnit_Framework_TestCase
{
    public function testBasicAuthCorrect()
    {
        $request = new Request('http://www.example.org/foo', "GET");
        $request->setBasicAuthUser('user');
        $request->setBasicAuthPass('pass');

        $basicAuth = new BasicAuthentication(
            function ($userId) {
                return password_hash('pass', PASSWORD_DEFAULT);
            },
            'realm'
        );
        $userInfo = $basicAuth->execute($request);
        $this->assertEquals('user', $userInfo->getUserId());
    }

    /**
     * @expectedException fkooman\Http\Exception\UnauthorizedException
     * @expectedExceptionMessage invalid_credentials
     */
    public function testBasicAuthWrongUser()
    {
        $request = new Request('http://www.example.org/foo', "GET");
        $request->setBasicAuthUser('wronguser');
        $request->setBasicAuthPass('pass');

        $basicAuth = new BasicAuthentication(
            function ($userId) {
                // we simulate not finding the userId 'wronguser'
                return false;
            },
            'realm'
        );
        $basicAuth->execute($request);
    }

    /**
     * @expectedException fkooman\Http\Exception\UnauthorizedException
     * @expectedExceptionMessage invalid_credentials
     */
    public function testBasicAuthWrongPass()
    {
        $request = new Request('http://www.example.org/foo', "GET");
        $request->setBasicAuthUser('user');
        $request->setBasicAuthPass('wrongpass');

        $basicAuth = new BasicAuthentication(
            function ($userId) {
                return 'someWrongHashValue';
            },
            'realm'
        );
        $basicAuth->execute($request);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage provided parameter is not callable
     */
    public function testUncallableParameter()
    {
        new BasicAuthentication('foo');
    }
}
