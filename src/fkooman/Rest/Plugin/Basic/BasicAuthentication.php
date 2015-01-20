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

namespace fkooman\Rest\Plugin\Basic;

use fkooman\Http\Request;
use fkooman\Rest\ServicePluginInterface;
use fkooman\Http\Exception\UnauthorizedException;

class BasicAuthentication implements ServicePluginInterface
{
    /** @var function */
    private $retrieveUserPassHash;

    /** @var string */
    private $basicAuthRealm;

    public function __construct(callable $retrieveUserPassHash, $basicAuthRealm = 'Protected Resource')
    {
        $this->retrieveUserPassHash = $retrieveUserPassHash;
        $this->basicAuthRealm = $basicAuthRealm;
    }

    public function execute(Request $request)
    {
        $requestBasicAuthUser = $request->getBasicAuthUser();
        $requestBasicAuthPass = $request->getBasicAuthPass();

        // retrieve the hashed password for given user
        $basicAuthPassHash = call_user_func($this->retrieveUserPassHash, $requestBasicAuthUser);

        if (false === $basicAuthPassHash || !password_verify($requestBasicAuthPass, $basicAuthPassHash)) {
            throw new UnauthorizedException(
                'invalid_credentials',
                'supplied username or password are invalid',
                'Basic',
                array(
                    'realm' => $this->basicAuthRealm,
                )
            );
        }

        return new BasicUserInfo($requestBasicAuthUser);
    }
}
