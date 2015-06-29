# Release History

## 0.6.1
- update spec file

## 0.6.0
- update `fkooman/rest`
- implement our own Basic header parsing for compatibility with PHP-FPM
- deal better with optional authentication
- update example

## 0.5.1
- update to latest `fkooman/rest` to support optional authentication

## 0.5.0
- update to latest `fkooman/rest`

## 0.4.0
- use `fkooman/rest` generic `UserInfo` class for user information

## 0.3.1
- fix PHP 5.3 support, the callable type hint is only available in 
  PHP >= 5.4

## 0.3.0
- **BREAKING**: we now require a function as the first parameter to the
  constructor that will take care of retrieving the password from the 
  provided userId instead of just providing a userId and password to
  the constructor, this makes it easy to support multiple users

## 0.2.2
- PHP >= 5.3.3 is enough
- update dependencies and example code

## 0.2.1
- require `fkooman/rest` stable version

## 0.2.0
- rename `UserInfo` to `BasicUserInfo`
- fix use of `UnauthorizedException`
- add example in `example` directory

## 0.1.0 
- initial release
