<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

final class TooManyRequestsException extends AbstractHttpStatusException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_TOO_MANY_REQUESTS;
    }

    protected function defaultErrorCode(): string
    {
        return 'TOO_MANY_REQUESTS';
    }
}
