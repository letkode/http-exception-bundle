# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.1.0] - 2026-09-29

### Added
- `listener_enabled` bundle option (default `true`). Set it to `false` to not register the `ExceptionListener`, for projects that handle exceptions with their own listener. The exceptions, contracts, `TranslationOption` and the locale resolver remain available.

---

## [1.0.0] - 2026-09-29

### Added
- HTTP status exceptions: `BadRequestException` (400), `UnauthorizedException` (401), `ForbiddenException` (403), `NotFoundException` and `EntityNotFoundException` (404), `MethodNotAllowedException` (405), `NotAcceptableException` (406), `ConflictException` (409), `GoneException` (410), `PreconditionFailedException` (412), `PayloadTooLargeException` (413), `UnsupportedMediaTypeException` (415), `UnprocessableEntityException` (422), `LockedException` (423), `PreconditionRequiredException` (428), `TooManyRequestsException` (429), `InternalServerErrorException` (500), `NotImplementedException` (501), `BadGatewayException` (502), `ServiceUnavailableException` (503), `GatewayTimeoutException` (504).
- `HttpStatusExceptionInterface` (`getStatusCode()`, `getErrorCode()`, `getOption()`) and `AbstractHttpStatusException`. Every exception has a default `errorCode` (for example `BAD_REQUEST`), overridable through the second constructor argument; the previous exception goes in the third argument (`previous:`), and `getCode()` is always 0.
- `TranslationOption` to translate an exception message in a chosen domain.
- `ExceptionListener`: renders these exceptions, Symfony `HttpExceptionInterface`, validation failures (422 with per-field `errors`) and unknown throwables as JSON, only for paths under `path_prefix`.
- `LocaleResolverInterface` with a request-based default, `RequestLocaleResolver`; replace it by re-aliasing the interface.
- English and Spanish `exceptions` translation catalog.
- Bundle configuration: `path_prefix` (default `/api`) and `listener_priority` (default `0`).
