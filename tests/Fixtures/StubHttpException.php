<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Tests\Fixtures;

use Letkode\HttpExceptionBundle\Exception\AbstractHttpStatusException;
use Symfony\Component\HttpFoundation\Response;

final class StubHttpException extends AbstractHttpStatusException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_I_AM_A_TEAPOT;
    }

    protected function defaultErrorCode(): string
    {
        return 'STUB';
    }
}
