<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

final class GoneException extends AbstractHttpStatusException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_GONE;
    }

    protected function defaultErrorCode(): string
    {
        return 'GONE';
    }
}
