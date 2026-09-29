<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

final class UnsupportedMediaTypeException extends AbstractHttpStatusException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_UNSUPPORTED_MEDIA_TYPE;
    }

    protected function defaultErrorCode(): string
    {
        return 'UNSUPPORTED_MEDIA_TYPE';
    }
}
