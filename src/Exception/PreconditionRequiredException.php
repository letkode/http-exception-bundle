<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

final class PreconditionRequiredException extends AbstractHttpStatusException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_PRECONDITION_REQUIRED;
    }

    protected function defaultErrorCode(): string
    {
        return 'PRECONDITION_REQUIRED';
    }
}
