<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

final class NotFoundException extends AbstractHttpStatusException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    protected function defaultErrorCode(): string
    {
        return 'NOT_FOUND';
    }
}
