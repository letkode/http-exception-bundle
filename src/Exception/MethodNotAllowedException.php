<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

final class MethodNotAllowedException extends AbstractHttpStatusException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_METHOD_NOT_ALLOWED;
    }

    protected function defaultErrorCode(): string
    {
        return 'METHOD_NOT_ALLOWED';
    }
}
