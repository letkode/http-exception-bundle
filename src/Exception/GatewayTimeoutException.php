<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class GatewayTimeoutException extends AbstractHttpStatusException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_GATEWAY_TIMEOUT;
    }

    protected function defaultErrorCode(): string
    {
        return 'GATEWAY_TIMEOUT';
    }
}
