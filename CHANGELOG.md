# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added
- HTTP status exceptions extracted from `letkode/common-bundle`: `BadRequestException` (400), `UnauthorizedException` (401), `ForbiddenException` (403), `NotFoundException` and `EntityNotFoundException` (404), `MethodNotAllowedException` (405), `NotAcceptableException` (406), `ConflictException` (409), `GoneException` (410), `PreconditionFailedException` (412), `PayloadTooLargeException` (413), `UnsupportedMediaTypeException` (415), `UnprocessableEntityException` (422), `LockedException` (423), `PreconditionRequiredException` (428), `TooManyRequestsException` (429), `InternalServerErrorException` (500), `NotImplementedException` (501), `BadGatewayException` (502), `ServiceUnavailableException` (503), `GatewayTimeoutException` (504).
- `HttpStatusExceptionInterface` now also declares `getErrorCode()` and `getOption()`.
- `TranslationOption` to translate an exception message in a chosen domain.
- `ExceptionListener`: renders these exceptions, Symfony `HttpExceptionInterface`, validation failures (422 with per-field `errors`) and unknown throwables as JSON, only for paths under `path_prefix`.
- `LocaleResolverInterface` with a request-based default, `RequestLocaleResolver`; replace it by re-aliasing the interface.
- English and Spanish `exceptions` translation catalog.
- Bundle configuration: `path_prefix` (default `/api`) and `listener_priority` (default `0`).

### Migration from `letkode/common-bundle`
```php
// Before (letkode/common-bundle 1.x)
// The unreleased development namespace Letkode\CommonBundle\Exception\Http\...
// (including ...\Http\Option\TranslationOption) maps the same way.
use Letkode\CommonBundle\Exception\BadRequestException;
use Letkode\CommonBundle\Exception\HttpStatusExceptionInterface;

// After
use Letkode\HttpExceptionBundle\Exception\BadRequestException;
use Letkode\HttpExceptionBundle\Contract\HttpStatusExceptionInterface;
use Letkode\HttpExceptionBundle\Option\TranslationOption;
```
Delete your application's own `ExceptionListener`; this bundle's replaces it.

### Changed
- The 2nd constructor argument of the exceptions is now the string `errorCode` (not an int `$code`); `getCode()` is always 0. Pass the previous exception as the 3rd argument / `previous:`.
