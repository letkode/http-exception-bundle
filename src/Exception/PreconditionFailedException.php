<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class PreconditionFailedException extends AbstractHttpStatusException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_PRECONDITION_FAILED;
    }

    protected function defaultErrorCode(): string
    {
        return 'PRECONDITION_FAILED';
    }
}
