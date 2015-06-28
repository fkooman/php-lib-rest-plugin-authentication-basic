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
require_once dirname(__DIR__).'/vendor/autoload.php';

use fkooman\Rest\Service;
use fkooman\Rest\PluginRegistry;
use fkooman\Rest\Plugin\Basic\BasicAuthentication;
use fkooman\Rest\Plugin\Basic\BasicUserInfo;
use fkooman\Http\Response;
use fkooman\Rest\ExceptionHandler;

ExceptionHandler::register();

$service = new Service();
$pluginRegistry = new PluginRegistry();
$pluginRegistry->registerDefaultPlugin(
    new BasicAuthentication(
        function ($userId) {
            // NOTE: password is generated using the "password_hash()"
            // function from PHP 5.6 or the ircmaxell/password-compat
            // library. This way no plain text passwords are stored
            // anywhere, below is a hashed value of 'bar'

            // this function should return the password hash of the
            // requested userId or false if no such user exists
            return $userId === 'foo' ? '$2y$10$ARD9Oq9xCzFANYGhv0mWxOsOallAS3qLQxLoOtzzRuLhv0U1IU9EO' : false;
        },
        array('realm' => 'My Secured Foo Service')
    )
);
$service->setPluginRegistry($pluginRegistry);

// make authentication optional, if it is provided we use it, but if
// not it is no big deal, don't forget to set the default value of
// UserInfo to null!
$service->get(
    '/',
    function (BasicUserInfo $u = null) {
        $response = new Response(200, 'text/plain');
        if (null === $u) {
            $response->setBody('Hello Anonymous!');
        } else {
            $response->setBody(sprintf('Hello %s!', $u->getUserId()));
        }

        return $response;
    },
    array(
        'fkooman\Rest\Plugin\Basic\BasicAuthentication' => array(
            'requireAuth' => false,
        ),
    )
);

// this route requires authentication
$service->get(
    '/secure',
    function (BasicUserInfo $u) {
        $response = new Response(200, 'text/plain');
        $response->setBody(sprintf('Hello %s!', $u->getUserId()));

        return $response;
    }
);

$service->run()->send();
