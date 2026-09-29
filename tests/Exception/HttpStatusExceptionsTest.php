<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Tests\Exception;

use Letkode\HttpExceptionBundle\Contract\HttpStatusExceptionInterface;
use Letkode\HttpExceptionBundle\Exception\AbstractHttpStatusException;
use Letkode\HttpExceptionBundle\Exception\BadGatewayException;
use Letkode\HttpExceptionBundle\Exception\BadRequestException;
use Letkode\HttpExceptionBundle\Exception\ConflictException;
use Letkode\HttpExceptionBundle\Exception\EntityNotFoundException;
use Letkode\HttpExceptionBundle\Exception\ForbiddenException;
use Letkode\HttpExceptionBundle\Exception\GatewayTimeoutException;
use Letkode\HttpExceptionBundle\Exception\GoneException;
use Letkode\HttpExceptionBundle\Exception\InternalServerErrorException;
use Letkode\HttpExceptionBundle\Exception\LockedException;
use Letkode\HttpExceptionBundle\Exception\MethodNotAllowedException;
use Letkode\HttpExceptionBundle\Exception\NotAcceptableException;
use Letkode\HttpExceptionBundle\Exception\NotFoundException;
use Letkode\HttpExceptionBundle\Exception\NotImplementedException;
use Letkode\HttpExceptionBundle\Exception\PayloadTooLargeException;
use Letkode\HttpExceptionBundle\Exception\PreconditionFailedException;
use Letkode\HttpExceptionBundle\Exception\PreconditionRequiredException;
use Letkode\HttpExceptionBundle\Exception\ServiceUnavailableException;
use Letkode\HttpExceptionBundle\Exception\TooManyRequestsException;
use Letkode\HttpExceptionBundle\Exception\UnauthorizedException;
use Letkode\HttpExceptionBundle\Exception\UnprocessableEntityException;
use Letkode\HttpExceptionBundle\Exception\UnsupportedMediaTypeException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class HttpStatusExceptionsTest extends TestCase
{
    /**
     * @return array<string, array{class-string<AbstractHttpStatusException>, int, string}>
     */
    public static function exceptionProvider(): array
    {
        return [
            'BadRequestException' => [BadRequestException::class, Response::HTTP_BAD_REQUEST, 'BAD_REQUEST'],
            'UnauthorizedException' => [UnauthorizedException::class, Response::HTTP_UNAUTHORIZED, 'UNAUTHORIZED'],
            'ForbiddenException' => [ForbiddenException::class, Response::HTTP_FORBIDDEN, 'FORBIDDEN'],
            'NotFoundException' => [NotFoundException::class, Response::HTTP_NOT_FOUND, 'NOT_FOUND'],
            'EntityNotFoundException' => [EntityNotFoundException::class, Response::HTTP_NOT_FOUND, 'ENTITY_NOT_FOUND'],
            'MethodNotAllowedException' => [MethodNotAllowedException::class, Response::HTTP_METHOD_NOT_ALLOWED, 'METHOD_NOT_ALLOWED'],
            'NotAcceptableException' => [NotAcceptableException::class, Response::HTTP_NOT_ACCEPTABLE, 'NOT_ACCEPTABLE'],
            'ConflictException' => [ConflictException::class, Response::HTTP_CONFLICT, 'CONFLICT'],
            'GoneException' => [GoneException::class, Response::HTTP_GONE, 'GONE'],
            'PreconditionFailedException' => [PreconditionFailedException::class, Response::HTTP_PRECONDITION_FAILED, 'PRECONDITION_FAILED'],
            'PayloadTooLargeException' => [PayloadTooLargeException::class, Response::HTTP_REQUEST_ENTITY_TOO_LARGE, 'PAYLOAD_TOO_LARGE'],
            'UnsupportedMediaTypeException' => [UnsupportedMediaTypeException::class, Response::HTTP_UNSUPPORTED_MEDIA_TYPE, 'UNSUPPORTED_MEDIA_TYPE'],
            'UnprocessableEntityException' => [UnprocessableEntityException::class, Response::HTTP_UNPROCESSABLE_ENTITY, 'UNPROCESSABLE_ENTITY'],
            'LockedException' => [LockedException::class, Response::HTTP_LOCKED, 'LOCKED'],
            'PreconditionRequiredException' => [PreconditionRequiredException::class, Response::HTTP_PRECONDITION_REQUIRED, 'PRECONDITION_REQUIRED'],
            'TooManyRequestsException' => [TooManyRequestsException::class, Response::HTTP_TOO_MANY_REQUESTS, 'TOO_MANY_REQUESTS'],
            'InternalServerErrorException' => [InternalServerErrorException::class, Response::HTTP_INTERNAL_SERVER_ERROR, 'INTERNAL_SERVER_ERROR'],
            'NotImplementedException' => [NotImplementedException::class, Response::HTTP_NOT_IMPLEMENTED, 'NOT_IMPLEMENTED'],
            'BadGatewayException' => [BadGatewayException::class, Response::HTTP_BAD_GATEWAY, 'BAD_GATEWAY'],
            'ServiceUnavailableException' => [ServiceUnavailableException::class, Response::HTTP_SERVICE_UNAVAILABLE, 'SERVICE_UNAVAILABLE'],
            'GatewayTimeoutException' => [GatewayTimeoutException::class, Response::HTTP_GATEWAY_TIMEOUT, 'GATEWAY_TIMEOUT'],
        ];
    }

    /**
     * @param class-string<AbstractHttpStatusException> $class
     */
    #[DataProvider('exceptionProvider')]
    public function testStatusCodeAndDefaultErrorCode(string $class, int $status, string $errorCode): void
    {
        $exception = new $class('error');

        self::assertSame($status, $exception->getStatusCode());
        self::assertSame($errorCode, $exception->getErrorCode());
        self::assertInstanceOf(HttpStatusExceptionInterface::class, $exception);
        self::assertInstanceOf(\RuntimeException::class, $exception);
    }

    /**
     * @param class-string<AbstractHttpStatusException> $class
     */
    #[DataProvider('exceptionProvider')]
    public function testErrorCodeCanBeOverridden(string $class, int $status, string $errorCode): void
    {
        self::assertSame('CUSTOM', (new $class('error', 'CUSTOM'))->getErrorCode());
    }

    /**
     * @param class-string<AbstractHttpStatusException> $class
     */
    #[DataProvider('exceptionProvider')]
    public function testCarriesMessageAndPrevious(string $class, int $status, string $errorCode): void
    {
        $previous = new \LogicException('root');
        $exception = new $class('Something went wrong.', previous: $previous);

        self::assertSame('Something went wrong.', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
    }

    /**
     * @param class-string<AbstractHttpStatusException> $class
     */
    #[DataProvider('exceptionProvider')]
    public function testIsThrowable(string $class, int $status, string $errorCode): void
    {
        $this->expectException($class);
        throw new $class('thrown');
    }
}
