<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

final class LockedException extends AbstractHttpStatusException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_LOCKED;
    }

    protected function defaultErrorCode(): string
    {
        return 'LOCKED';
    }
}
