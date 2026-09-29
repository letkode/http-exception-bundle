<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

final class BadGatewayException extends AbstractHttpStatusException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_GATEWAY;
    }

    protected function defaultErrorCode(): string
    {
        return 'BAD_GATEWAY';
    }
}
