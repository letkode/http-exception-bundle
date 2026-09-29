<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class UnprocessableEntityException extends AbstractHttpStatusException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }

    protected function defaultErrorCode(): string
    {
        return 'UNPROCESSABLE_ENTITY';
    }
}
