<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class BadRequestException extends AbstractHttpStatusException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    protected function defaultErrorCode(): string
    {
        return 'BAD_REQUEST';
    }
}
