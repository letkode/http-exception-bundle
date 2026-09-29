<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

final class ConflictException extends AbstractHttpStatusException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_CONFLICT;
    }

    protected function defaultErrorCode(): string
    {
        return 'CONFLICT';
    }
}
