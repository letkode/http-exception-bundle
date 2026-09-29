<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class UnauthorizedException extends AbstractHttpStatusException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_UNAUTHORIZED;
    }

    protected function defaultErrorCode(): string
    {
        return 'UNAUTHORIZED';
    }
}
