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

use fkooman\Http\Exception\UnauthorizedException;
use fkooman\Http\Request;
use fkooman\Rest\Plugin\Authentication\AuthenticationPluginInterface;
use InvalidArgumentException;
use fkooman\Rest\Service;

class BasicAuthentication implements AuthenticationPluginInterface
{
    /** @var callable */
    private $retrieveHash;

    /** @var array */
    private $authParams;

    public function __construct($retrieveHash, array $authParams = array())
    {
        if (!is_callable($retrieveHash)) {
            throw new InvalidArgumentException('argument must be callable');
        }
        $this->retrieveHash = $retrieveHash;
        if (!array_key_exists('realm', $authParams)) {
            $authParams['realm'] = 'Protected Resource';
        }
        $this->authParams = $authParams;
    }

    public function isAuthenticated(Request $request)
    {
        $authHeader = $request->getHeader('Authorization');
        if (!self::isAttempt($authHeader)) {
            // no attempt
            return false;
        }
        $encodedUserPass = substr($authHeader, 6);

        // it is an attempt
        $authUserPass = self::extractAuthUserPass($encodedUserPass);
        if (false === $authUserPass) {
            return false;
        }

        // retrieve the hashed password for given user
        $passHash = call_user_func($this->retrieveHash, $authUserPass['authUser']);
        if (false === $passHash) {
            // user does not exist
            return false;
        }
        if (!password_verify($authUserPass['authPass'], $passHash)) {
            // invalid password
            return false;
        }

        return new BasicUserInfo($authUserPass['authUser']);
    }

    public function requestAuthentication(Request $request)
    {
        $authHeader = $request->getHeader('Authorization');
        if (self::isAttempt($authHeader)) {
            // there was an attempt, and it failed, otherwise we wouldn't be
            // here
            $e = new UnauthorizedException(
                'invalid_credentials',
                'provided credentials not valid'
            );
        } else {
            // no attempt
            $e = new UnauthorizedException(
                'no_credentials',
                'credentials must be provided'
            );
        }
        $e->addScheme('Basic', $this->authParams);

        throw $e;
    }

    public function init(Service $service)
    {
        // NOP 
    }

    private static function isAttempt($authHeader)
    {
        if (null === $authHeader) {
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

    /**
     * Extract the authUser and authPass from the BASE64 encoded string.
     *
     * @param string $encodedString the BASE64 encoded colon separated
     *                              authUser and authPass
     *
     * @return mixed array containing authUser and authPass or false if
     *               the decoding fails
     */
    private static function extractAuthUserPass($encodedString)
    {
        if (0 >= strlen($encodedString)) {
            return false;
        }
        $decodedString = base64_decode($encodedString, true);
        if (false === $decodedString) {
            return false;
        }

        $firstColonPos = strpos($decodedString, ':');
        if (false === $firstColonPos) {
            return false;
        }

        return array(
            'authUser' => substr($decodedString, 0, $firstColonPos),
            'authPass' => substr($decodedString, $firstColonPos + 1),
        );
    }
}
