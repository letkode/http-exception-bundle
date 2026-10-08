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

To get a commented copy of the config in your project:

```bash
bin/console letkode:config:publish http-exception
```

It writes `config/packages/letkode_http_exception.yaml` and never overwrites an existing file unless you add `--force`. `--dry-run` shows what it would do.

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

### Extending the exceptions

The exceptions are not `final`. Create a subclass for a domain case and override `defaultErrorCode()` to give it its own code:

```php
use Letkode\HttpExceptionBundle\Exception\EntityNotFoundException;

class UserNotFoundException extends EntityNotFoundException
{
    protected function defaultErrorCode(): string
    {
        return 'USER_NOT_FOUND';
    }
}
```

Anything that catches `EntityNotFoundException` (or renders it) also handles the subclass. Your own exception class can also extend `AbstractHttpStatusException` (implement `getStatusCode()` and `defaultErrorCode()`) or implement `HttpStatusExceptionInterface`.

### Errors by field

Attach an `ErrorsOption` to include an `errors` object in the response, keyed by field. Each message is a string or a Symfony `TranslatableInterface`, which is translated with the request locale:

```php
use Letkode\HttpExceptionBundle\Exception\UnprocessableEntityException;
use Letkode\HttpExceptionBundle\Option\ErrorsOption;

throw new UnprocessableEntityException(
    'Invalid input.',
    'INVALID_INPUT',
    options: [new ErrorsOption(['name' => ['Required.'], 'items[0].sku' => [new MyTranslatableMessage()]])],
);
```

```json
{ "success": false, "message": "Invalid input.", "status": 422, "errorCode": "INVALID_INPUT", "errors": { "name": ["Required."], "items[0].sku": ["..."] } }
```

Without the option the response has no `errors` key.

### Other exceptions

| Thrown | Response |
|---|---|
| `UnprocessableEntityHttpException` wrapping a `ValidationFailedException` | 422, `errors` grouped by field — or the mapped status when every violation's constraint is in `validation.status_by_constraint` (see below) |
| Any Symfony `HttpExceptionInterface` | its status; framework messages replaced by `http.<status>` / `http.default`; `traces` in debug |
| Anything else | 500 with the `http.500` message, details only in the log |

### Validation status by constraint

A validation failure normally responds 422. When **every** violation comes from a constraint mapped
in `validation.status_by_constraint` and they all map to the same status, that status is used
instead. Out of the box, uniqueness constraints respond **409** — no configuration needed:

| Constraint (default) | Status |
|---|---|
| `Letkode\CommonBundle\Attribute\Constraint\UniqueField\UniqueField` | 409 |
| `Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity` | 409 |

```json
{ "success": false, "message": "The resource already exists.", "status": 409, "errorCode": "CONFLICT", "errors": { "taxId": ["..."] } }
```

Mixed violations (e.g. a malformed email plus a duplicate) stay 422. To never mix them, validate
uniqueness in a later group with a `GroupSequence` on the DTO.

Add your own mappings or disable a default (your entries are merged on top of the defaults;
subclasses inherit the mapping; classes that are not installed are ignored):

```yaml
letkode_http_exception:
    validation:
        status_by_constraint:
            App\Validator\NotLocked: 423
            Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity: ~
```

This only affects HTTP responses built by the listener; validating in a command returns the
violations as usual.

### Translations

The bundle ships `exceptions.en.yaml` and `exceptions.es.yaml` (keys `http.<status>`, `http.default`, `validation.failed`, `validation.conflict`). Override any key by defining it in your application's `translations/exceptions.<locale>.yaml`.

### Locale

Messages are translated with the locale of the current request. To change that, implement `Letkode\HttpExceptionBundle\Contract\LocaleResolverInterface` and alias it in your `services.yaml`:

```yaml
Letkode\HttpExceptionBundle\Contract\LocaleResolverInterface: '@App\Locale\MyLocaleResolver'
```

---

## License

MIT
