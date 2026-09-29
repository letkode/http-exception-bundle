<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class PayloadTooLargeException extends AbstractHttpStatusException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_REQUEST_ENTITY_TOO_LARGE;
    }

    protected function defaultErrorCode(): string
    {
        return 'PAYLOAD_TOO_LARGE';
    }
}
