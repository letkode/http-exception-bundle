<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

final class ServiceUnavailableException extends AbstractHttpStatusException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_SERVICE_UNAVAILABLE;
    }

    protected function defaultErrorCode(): string
    {
        return 'SERVICE_UNAVAILABLE';
    }
}
