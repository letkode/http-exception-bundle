<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Option;

use Letkode\HttpExceptionBundle\Contract\ExceptionOptionInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Errors by field, rendered by the ExceptionListener under the `errors` key of the response.
 *
 * Each message is either a plain string or a Symfony TranslatableInterface, which the listener translates
 * with the locale it resolves for the request.
 */
final readonly class ErrorsOption implements ExceptionOptionInterface
{
    /**
     * @param array<string, list<string|TranslatableInterface>> $errors
     */
    public function __construct(public array $errors)
    {
    }
}
