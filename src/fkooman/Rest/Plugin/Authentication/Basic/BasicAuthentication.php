<?php

/**
 * Copyright 2015 François Kooman <fkooman@tuxed.net>.
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

use fkooman\Http\Exception\BadRequestException;
use fkooman\Http\Exception\UnauthorizedException;
use fkooman\Http\Request;
use fkooman\Rest\Plugin\Authentication\AuthenticationPluginInterface;
use InvalidArgumentException;

class BasicAuthentication implements AuthenticationPluginInterface
{
    /** @var callable */
    private $retrieveHash;

    /** @var array */
    private $authParams;

    public function __construct($retrieveHash, array $authParams = array())
    {
        // type hint 'callable' works only in >= PHP 5.4
        if (!is_callable($retrieveHash)) {
            throw new InvalidArgumentException('provided parameter is not callable');
        }
        $this->retrieveHash = $retrieveHash;
        if (!array_key_exists('realm', $authParams)) {
            $authParams['realm'] = 'Protected Resource';
        }
        $this->authParams = $authParams;
    }

    public function getScheme()
    {
        return 'Basic';
    }

    public function getAuthParams()
    {
        return $this->authParams;
    }

    public function isAttempt(Request $request)
    {
        $authHeader = $request->getHeader('Authorization');
        if (null === $authHeader) {
            return false;
        }
        if (!is_string($authHeader)) {
            return false;
        }
        if (6 >= strlen($authHeader)) {
            return false;
        }
        if (0 !== strpos($authHeader, 'Basic ')) {
            return false;
        }

        return true;
    }

    public function execute(Request $request, array $routeConfig)
    {
        if ($this->isAttempt($request)) {
            // if there is an attempt, it MUST succeed
            $authHeader = $request->getHeader('Authorization');
            $authUserPass = self::extractUserPass(substr($authHeader, 6));
            if (false === $authUserPass) {
                // problem in getting the authUser and authPass
                throw new BadRequestException('unable to decode authUser and/or authPass');
            }
            list($authUser, $authPass) = $authUserPass;

            // retrieve the hashed password for given user
            $passHash = call_user_func($this->retrieveHash, $authUser);
            if (false === $passHash || !password_verify($authPass, $passHash)) {
                $e = new UnauthorizedException(
                    'invalid_credentials',
                    'provided credentials not valid'
                );
                $e->addScheme('Basic', $this->authParams);
                throw $e;
            }

            return new BasicUserInfo($authUser);
        }

        // if there is no attempt, and authentication is not required,
        // then we can let it go :)
        if (array_key_exists('requireAuth', $routeConfig)) {
            if (!$routeConfig['requireAuth']) {
                return;
            }
        }

        $e = new UnauthorizedException(
            'no_credentials',
            'credentials must be provided'
        );
        $e->addScheme('Basic', $this->authParams);

        throw $e;
    }

    /**
     * Extract the authUser and authPass from the BASE64 encoded string.
     *
     * @param string $encodedString the BASE64 encoded colon separated
     *                              authUser and authPass
     *
     * @return mixed array containing authUser and authPass or false if
     *               the decoding fails
     */
    public static function extractUserPass($encodedString)
    {
        if (0 >= strlen($encodedString)) {
            return false;
        }
        $decodedString = base64_decode($encodedString, true);
        if (false === $decodedString) {
            return false;
        }

        if (false === strpos($decodedString, ':')) {
            return false;
        }

        return explode(':', $decodedString, 2);
    }
}
