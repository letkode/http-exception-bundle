<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Tests\EventListener;

use Letkode\HttpExceptionBundle\EventListener\ExceptionListener;
use Letkode\HttpExceptionBundle\Exception\BadRequestException;
use Letkode\HttpExceptionBundle\Exception\InternalServerErrorException;
use Letkode\HttpExceptionBundle\Exception\NotFoundException;
use Letkode\HttpExceptionBundle\Exception\UnprocessableEntityException;
use Letkode\HttpExceptionBundle\Option\ErrorsOption;
use Letkode\HttpExceptionBundle\Option\TranslationOption;
use Letkode\HttpExceptionBundle\Tests\Fixtures\FakeTranslator;
use Letkode\HttpExceptionBundle\Tests\Fixtures\FixedLocaleResolver;
use Letkode\HttpExceptionBundle\Tests\Fixtures\RecordingLogger;
use Letkode\HttpExceptionBundle\Tests\Fixtures\StubTranslatable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ExceptionListenerTest extends TestCase
{
    private RecordingLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new RecordingLogger();
    }

    /**
     * @param array<string, string> $messages
     */
    private function handle(
        \Throwable $throwable,
        string $path = '/api/things',
        bool $debug = false,
        array $messages = [],
        string|null $locale = 'es',
        FakeTranslator|null $translator = null,
    ): ExceptionEvent {
        $listener = new ExceptionListener(
            $translator ?? new FakeTranslator($messages),
            $this->logger,
            new FixedLocaleResolver($locale),
            $debug,
            '/api',
        );

        $event = new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create($path),
            HttpKernelInterface::MAIN_REQUEST,
            $throwable,
        );

        $listener($event);

        return $event;
    }

    /**
     * @return array<string, mixed>
     */
    private function body(ExceptionEvent $event): array
    {
        $response = $event->getResponse();
        self::assertNotNull($response, 'The listener did not set a response.');

        $decoded = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }

    public function testIgnoresRequestsOutsidePathPrefix(): void
    {
        $event = $this->handle(new NotFoundException('nope'), '/health');

        self::assertNull($event->getResponse());
        self::assertSame([], $this->logger->records);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function handledPathProvider(): array
    {
        return ['exact prefix' => ['/api'], 'sub path' => ['/api/users/1']];
    }

    #[DataProvider('handledPathProvider')]
    public function testHandlesPathEqualToPrefixAndSubPaths(string $path): void
    {
        $event = $this->handle(new NotFoundException('nope'), $path);

        self::assertNotNull($event->getResponse());
    }

    public function testDomainExceptionBecomesJson(): void
    {
        $event = $this->handle(new NotFoundException('User missing.', 'USER_MISSING'));

        self::assertSame(404, $event->getResponse()?->getStatusCode());
        self::assertSame(
            ['success' => false, 'message' => 'User missing.', 'status' => 404, 'errorCode' => 'USER_MISSING'],
            $this->body($event),
        );
        self::assertSame([], $this->logger->records);
    }

    public function testDomainExceptionUsesItsDefaultErrorCode(): void
    {
        $body = $this->body($this->handle(new NotFoundException('User missing.')));

        self::assertSame('NOT_FOUND', $body['errorCode']);
    }

    public function testTranslatableDomainExceptionIsTranslatedWithOptionAndResolverLocale(): void
    {
        $translator = new FakeTranslator(['custom|errors.bad' => 'Solicitud mala']);
        $exception = new BadRequestException(
            'errors.bad',
            options: [new TranslationOption(domain: 'custom', parameters: ['%n%' => 3])],
        );

        $body = $this->body($this->handle($exception, translator: $translator));

        self::assertSame('Solicitud mala', $body['message']);
        self::assertSame(
            [['id' => 'errors.bad', 'parameters' => ['%n%' => 3], 'domain' => 'custom', 'locale' => 'es']],
            $translator->calls,
        );
    }

    public function testNonTranslatableOptionReturnsRawMessage(): void
    {
        $translator = new FakeTranslator(['exceptions|errors.bad' => 'should not be used']);
        $exception = new BadRequestException('errors.bad', options: [new TranslationOption(isTranslatable: false)]);

        $body = $this->body($this->handle($exception, translator: $translator));

        self::assertSame('errors.bad', $body['message']);
        self::assertSame([], $translator->calls);
    }

    public function testExceptionWithoutOptionIsNotTranslated(): void
    {
        $translator = new FakeTranslator(['exceptions|errors.bad' => 'should not be used']);

        $body = $this->body($this->handle(new BadRequestException('errors.bad'), translator: $translator));

        self::assertSame('errors.bad', $body['message']);
        self::assertSame([], $translator->calls);
    }

    public function testServerErrorDomainExceptionIsLoggedAsCritical(): void
    {
        $event = $this->handle(new InternalServerErrorException('DB down.'));

        self::assertSame(500, $event->getResponse()?->getStatusCode());
        self::assertCount(1, $this->logger->withLevel('critical'));
        self::assertSame('DB down.', $this->logger->withLevel('critical')[0]['message']);
    }

    public function testValidationFailureBuildsPerFieldErrors(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('Too short.', null, [], null, 'name', 'x'),
            new ConstraintViolation('Not blank.', null, [], null, 'name', ''),
            new ConstraintViolation('Invalid sku.', null, [], null, 'items[0].sku', 'zz'),
        ]);
        $exception = new UnprocessableEntityHttpException('Validation failed', new ValidationFailedException('payload', $violations));

        $event = $this->handle($exception, messages: ['exceptions|validation.failed' => 'La validación falló.']);
        $body = $this->body($event);

        self::assertSame(422, $event->getResponse()?->getStatusCode());
        self::assertSame('La validación falló.', $body['message']);
        self::assertSame(422, $body['status']);
        self::assertNull($body['errorCode']);
        self::assertSame(
            ['name' => ['Too short.', 'Not blank.'], 'items[0].sku' => ['Invalid sku.']],
            $body['errors'],
        );
    }

    public function testSymfonyHttpExceptionKeepsCustomMessageAndHeaders(): void
    {
        $event = $this->handle(new HttpException(429, 'Slow down.', null, ['Retry-After' => '10']));
        $body = $this->body($event);

        self::assertSame(429, $event->getResponse()?->getStatusCode());
        self::assertSame('10', $event->getResponse()->headers->get('Retry-After'));
        self::assertSame('Slow down.', $body['message']);
        self::assertSame(429, $body['status']);
        self::assertNull($body['errorCode']);
        self::assertSame('warning', $this->logger->records[0]['level']);
        self::assertSame('/api/things', $this->logger->records[0]['context']['path']);
    }

    public function testFrameworkMessageIsReplacedByTheStatusTranslation(): void
    {
        $body = $this->body($this->handle(
            new NotFoundHttpException('No route found for "GET /api/x"'),
            messages: ['exceptions|http.404' => 'No existe.'],
        ));

        self::assertSame('No existe.', $body['message']);
    }

    public function testFallsBackToDefaultKeyWhenStatusIsUntranslated(): void
    {
        $body = $this->body($this->handle(
            new HttpException(418, ''),
            messages: ['exceptions|http.default' => 'Algo salió mal.'],
        ));

        self::assertSame('Algo salió mal.', $body['message']);
        self::assertSame(418, $body['status']);
    }

    public function testTracesAreOnlyIncludedInDebugAndAreSerializable(): void
    {
        $circular = new \stdClass();
        $circular->self = $circular;

        // array_map() puts a frame without file/line in the trace; $circular would break json_encode() if args leaked.
        $capture = static function () use ($circular): HttpException {
            try {
                array_map(static function (object $o): never {
                    throw new HttpException(400, 'Bad input.');
                }, [$circular]);
            } catch (HttpException $e) {
                return $e;
            }

            throw new \LogicException('unreachable');
        };
        $exception = $capture();

        $debug = $this->body($this->handle($exception, debug: true));
        $production = $this->body($this->handle($exception, debug: false));

        self::assertIsArray($debug['traces']);
        self::assertNotEmpty($debug['traces']);
        foreach ($debug['traces'] as $frame) {
            self::assertIsArray($frame);
            self::assertSame(['file', 'line', 'function', 'class'], array_keys($frame));
            self::assertIsString($frame['file']);
            self::assertIsInt($frame['line']);
            self::assertArrayNotHasKey('args', $frame);
        }
        self::assertSame([], $production['traces']);
    }

    public function testUnknownThrowableBecomesAGenericServerError(): void
    {
        $event = $this->handle(
            new \LogicException('secret internals'),
            messages: ['exceptions|http.500' => 'Error interno.'],
        );
        $body = $this->body($event);

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $event->getResponse()?->getStatusCode());
        self::assertSame('Error interno.', $body['message']);
        self::assertNull($body['errorCode']);
        self::assertArrayNotHasKey('traces', $body);
        self::assertStringNotContainsString('secret internals', (string) $event->getResponse()->getContent());

        $critical = $this->logger->withLevel('critical');
        self::assertCount(1, $critical);
        self::assertSame('secret internals', $critical[0]['message']);
        self::assertSame('/api/things', $critical[0]['context']['path']);
    }

    public function testNullLocaleIsPassedThroughToTheTranslator(): void
    {
        $translator = new FakeTranslator(['exceptions|http.500' => 'Internal error.']);

        $body = $this->body($this->handle(new \LogicException('x'), locale: null, translator: $translator));

        self::assertSame('Internal error.', $body['message']);
        self::assertNull($translator->calls[0]['locale']);
    }

    public function testDomainExceptionWithErrorsOptionRendersTheErrorsByField(): void
    {
        $exception = new UnprocessableEntityException(
            'Invalid input.',
            'INVALID_INPUT',
            options: [new ErrorsOption(['name' => ['Required.', 'Too short.'], 'items[0].sku' => ['Invalid.']])],
        );

        $event = $this->handle($exception);
        $body = $this->body($event);

        self::assertSame(422, $event->getResponse()?->getStatusCode());
        self::assertSame('INVALID_INPUT', $body['errorCode']);
        self::assertSame('Invalid input.', $body['message']);
        self::assertSame(['name' => ['Required.', 'Too short.'], 'items[0].sku' => ['Invalid.']], $body['errors']);
    }

    public function testTranslatableErrorsAreTranslatedWithTheResolvedLocale(): void
    {
        $translator = new FakeTranslator(['query_filter|query_filter.not_sortable' => 'No se permite ordenar por «x».']);
        $exception = new UnprocessableEntityException('Invalid input.', options: [new ErrorsOption([
            'sort' => [new StubTranslatable('query_filter.not_sortable', ['%value%' => 'x'], 'query_filter'), 'Plain text.'],
        ])]);

        $body = $this->body($this->handle($exception, translator: $translator));

        self::assertSame(['sort' => ['No se permite ordenar por «x».', 'Plain text.']], $body['errors']);
        self::assertSame(
            [['id' => 'query_filter.not_sortable', 'parameters' => ['%value%' => 'x'], 'domain' => 'query_filter', 'locale' => 'es']],
            $translator->calls,
        );
    }

    public function testTranslatableErrorsReceiveANullLocaleWhenThereIsNone(): void
    {
        $translator = new FakeTranslator();
        $exception = new UnprocessableEntityException('Invalid input.', options: [new ErrorsOption([
            'sort' => [new StubTranslatable('query_filter.not_sortable')],
        ])]);

        $this->handle($exception, locale: null, translator: $translator);

        self::assertNull($translator->calls[0]['locale']);
    }

    public function testDomainExceptionWithoutErrorsOptionHasNoErrorsKey(): void
    {
        $body = $this->body($this->handle(new UnprocessableEntityException('Invalid input.')));

        self::assertArrayNotHasKey('errors', $body);
    }
}
