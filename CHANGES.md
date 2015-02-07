# Release History

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
