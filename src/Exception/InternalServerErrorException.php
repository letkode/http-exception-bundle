<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class InternalServerErrorException extends AbstractHttpStatusException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    protected function defaultErrorCode(): string
    {
        return 'INTERNAL_SERVER_ERROR';
    }
}
