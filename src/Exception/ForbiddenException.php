<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class ForbiddenException extends AbstractHttpStatusException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }

    protected function defaultErrorCode(): string
    {
        return 'FORBIDDEN';
    }
}
