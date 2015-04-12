<?php

/**
* Copyright 2015 François Kooman <fkooman@tuxed.net>
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
use InvalidArgumentException;
use fkooman\Rest\Plugin\UserInfo;

class BasicAuthentication implements ServicePluginInterface
{
    /** @var function */
    private $retrieveHash;

    /** @var string */
    private $realm;

    public function __construct($retrieveHash, $realm = 'Protected Resource')
    {
        // type hint 'callable' works only in >= PHP 5.4
        if (!is_callable($retrieveHash)) {
            throw new InvalidArgumentException('provided parameter is not callable');
        }
        $this->retrieveHash = $retrieveHash;
        $this->realm = $realm;
    }

    public function execute(Request $request, array $routeConfig)
    {
        $authUser = $request->getBasicAuthUser();
        $authPass = $request->getBasicAuthPass();

        // retrieve the hashed password for given user
        $passHash = call_user_func($this->retrieveHash, $authUser);

        if (!password_verify($authPass, $passHash)) {
            // check if authentication is required...
            if (array_key_exists('requireAuth', $routeConfig)) {
                if (!$routeConfig['requireAuth']) {
                    return false;
                }
            }
            
            throw new UnauthorizedException(
                'invalid_credentials',
                'supplied username or password are invalid',
                'Basic',
                array(
                    'realm' => $this->realm,
                )
            );
        }

        return new UserInfo($authUser);
    }
}
