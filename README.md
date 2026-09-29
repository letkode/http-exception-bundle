# letkode/http-exception-bundle

HTTP status exceptions and a JSON exception listener for Symfony applications.

---

## Installation

```bash
composer require letkode/http-exception-bundle
```

Symfony Flex registers the bundle automatically. Otherwise:

```php
// config/bundles.php
return [
    Letkode\HttpExceptionBundle\LetkodeHttpExceptionBundle::class => ['all' => true],
];
```

Requires a configured Symfony translator and a PSR-3 logger. Install `symfony/validator` to get per-field errors on 422 responses.

---

## Configuration

All options are optional; defaults are shown.

```yaml
# config/packages/letkode_http_exception.yaml
letkode_http_exception:
    path_prefix: /api        # only requests whose path starts with this are handled
    listener_enabled: true   # set to false to not register the ExceptionListener
    listener_priority: 0     # priority of the kernel.exception listener
```

`path_prefix` is matched as a plain string prefix, so `/api` also matches `/apiary`. An empty string (`''`) makes the listener handle every path.

Debug traces follow `%kernel.debug%`; there is nothing to configure.

### Using your own exception listener

To handle exceptions with your own listener, turn the bundle's off:

```yaml
letkode_http_exception:
    listener_enabled: false
```

The exceptions, the contracts, `TranslationOption` and the locale resolver stay available; only the `kernel.exception` listener is not registered. Your listener can keep relying on `HttpStatusExceptionInterface` (`getStatusCode()`, `getErrorCode()`, `getOption()`).

---

## Usage

Throw an exception from any service; the listener turns it into JSON.

```php
use Letkode\HttpExceptionBundle\Exception\EntityNotFoundException;

throw new EntityNotFoundException('User not found.');
throw new EntityNotFoundException('User not found.', 'USER_NOT_FOUND'); // custom errorCode
```

```json
{ "success": false, "message": "User not found.", "status": 404, "errorCode": "USER_NOT_FOUND" }
```

Every exception has a default `errorCode` (for example `BAD_REQUEST`), overridable through the second constructor argument.

### Exceptions

| Class | HTTP | Default `errorCode` |
|---|---|---|
| `BadRequestException` | 400 | `BAD_REQUEST` |
| `UnauthorizedException` | 401 | `UNAUTHORIZED` |
| `ForbiddenException` | 403 | `FORBIDDEN` |
| `NotFoundException` | 404 | `NOT_FOUND` |
| `EntityNotFoundException` | 404 | `ENTITY_NOT_FOUND` |
| `MethodNotAllowedException` | 405 | `METHOD_NOT_ALLOWED` |
| `NotAcceptableException` | 406 | `NOT_ACCEPTABLE` |
| `ConflictException` | 409 | `CONFLICT` |
| `GoneException` | 410 | `GONE` |
| `PreconditionFailedException` | 412 | `PRECONDITION_FAILED` |
| `PayloadTooLargeException` | 413 | `PAYLOAD_TOO_LARGE` |
| `UnsupportedMediaTypeException` | 415 | `UNSUPPORTED_MEDIA_TYPE` |
| `UnprocessableEntityException` | 422 | `UNPROCESSABLE_ENTITY` |
| `LockedException` | 423 | `LOCKED` |
| `PreconditionRequiredException` | 428 | `PRECONDITION_REQUIRED` |
| `TooManyRequestsException` | 429 | `TOO_MANY_REQUESTS` |
| `InternalServerErrorException` | 500 | `INTERNAL_SERVER_ERROR` |
| `NotImplementedException` | 501 | `NOT_IMPLEMENTED` |
| `BadGatewayException` | 502 | `BAD_GATEWAY` |
| `ServiceUnavailableException` | 503 | `SERVICE_UNAVAILABLE` |
| `GatewayTimeoutException` | 504 | `GATEWAY_TIMEOUT` |

Server errors (5xx) are logged as `critical`.

### Translated messages

Pass a `TranslationOption` to translate the message (which then acts as a translation key):

```php
use Letkode\HttpExceptionBundle\Exception\BadRequestException;
use Letkode\HttpExceptionBundle\Option\TranslationOption;

throw new BadRequestException(
    'errors.invalid_range',
    options: [new TranslationOption(domain: 'exceptions', parameters: ['%max%' => 10])],
);
```

Without a `TranslationOption` the message is returned as is.

### Other exceptions

| Thrown | Response |
|---|---|
| `UnprocessableEntityHttpException` wrapping a `ValidationFailedException` | 422, `errors` grouped by field |
| Any Symfony `HttpExceptionInterface` | its status; framework messages replaced by `http.<status>` / `http.default`; `traces` in debug |
| Anything else | 500 with the `http.500` message, details only in the log |

### Translations

The bundle ships `exceptions.en.yaml` and `exceptions.es.yaml` (keys `http.<status>`, `http.default`, `validation.failed`). Override any key by defining it in your application's `translations/exceptions.<locale>.yaml`.

### Locale

Messages are translated with the locale of the current request. To change that, implement `Letkode\HttpExceptionBundle\Contract\LocaleResolverInterface` and alias it in your `services.yaml`:

```yaml
Letkode\HttpExceptionBundle\Contract\LocaleResolverInterface: '@App\Locale\MyLocaleResolver'
```

---

## License

MIT
