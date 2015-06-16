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
namespace fkooman\Rest\Plugin\Basic;

use fkooman\Http\Exception\BadRequestException;
use fkooman\Http\Exception\UnauthorizedException;
use fkooman\Http\Request;
use fkooman\Rest\Plugin\Authentication\AuthenticationPluginInterface;
use InvalidArgumentException;

class BasicAuthentication implements AuthenticationPluginInterface
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

    public function getRealm()
    {
        return $this->realm;
    }

    public function getAuthScheme()
    {
        return 'Basic';
    }

    public function execute(Request $request, array $routeConfig)
    {
        if ($this->isAttempt($request)) {
            // if there is an attempt, it MUST succeed
            $authHeader = $request->getHeader('Authorization');
            $authUserPass = $this->getAuthUserPass($authHeader);
            if (false === $authUserPass) {
                // problem in getting the authUser and authPass
                throw new BadRequestException('unable to decode authUser and/or authPass');
            }
            list($authUser, $authPass) = $authUserPass;

            // retrieve the hashed password for given user
            $passHash = call_user_func($this->retrieveHash, $authUser);

            if (!password_verify($authPass, $passHash)) {
                throw new UnauthorizedException(
                    'invalid_credentials',
                    'provided credentials not valid',
                    'Basic',
                    array(
                        'realm' => $this->realm,
                    )
                );
            }

            return new BasicUserInfo($authUser);
        } else {
            // if there is no attempt, and authentication is not required,
            // then we can let it go :)
            if (array_key_exists('requireAuth', $routeConfig)) {
                if (!$routeConfig['requireAuth']) {
                    return false;
                }
            }
            throw new UnauthorizedException(
                'no_credentials',
                'credentials must be provided',
                'Basic',
                array(
                    'realm' => $this->realm,
                )
            );
        }
    }

    public function getScheme()
    {
        return 'Basic';
    }

    public function isAttempt(Request $request)
    {
        $authHeader = $request->getHeader('Authorization');
        if (null === $authHeader) {
            return false;
        }
        if (0 !== strpos($authHeader, 'Basic ')) {
            return false;
        }

        return true;
    }

    /**
     * Extract the authUser and andPass from the BASE64 encoded string.
     *
     * @param string $encodedString the BASE64 encoded colon separated
     *                              authUser and authPass
     *
     * @return array|false array containing authUser and authPass or false if
     *                     the decoding fails
     */
    private function extractUserPass($encodedString)
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

    /**
     * Extracts the authUser and authPass from the Basic Authoriziation header.
     *
     * @param string $authHeader the Basic header, e.g. 'Basic Zm9vOmJhcg=='
     *
     * @return array the base64 decoded password from the Basic header
     *
     * NOTE: if any of the steps fail, finding the 'Basic ' prefix, decoding
     * the base64 string, finding an empty authUser or authPass the resulting
     * authUser and authPass will both be 'null'.
     */
    private function getAuthUserPass($authHeader)
    {
        #        if (!$this->isAttempt($authHeader)) {
#            return false;
#        }

        return $this->extractUserPass(substr($authHeader, 6));
    }
}
