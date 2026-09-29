<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

final class NotImplementedException extends AbstractHttpStatusException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_IMPLEMENTED;
    }

    protected function defaultErrorCode(): string
    {
        return 'NOT_IMPLEMENTED';
    }
}
