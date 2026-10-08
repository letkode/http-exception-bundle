<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\EventListener;

use Letkode\HttpExceptionBundle\Contract\HttpStatusExceptionInterface;
use Letkode\HttpExceptionBundle\Contract\LocaleResolverInterface;
use Letkode\HttpExceptionBundle\Option\ErrorsOption;
use Letkode\HttpExceptionBundle\Option\TranslationOption;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ExceptionListener
{
    /**
     * @param array<string, int> $statusByConstraint constraint FQCN => HTTP status for validation failures
     */
    public function __construct(
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
        private LocaleResolverInterface $localeResolver,
        private bool $debug,
        private string $pathPrefix,
        private array $statusByConstraint = [],
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), $this->pathPrefix)) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof HttpStatusExceptionInterface) {
            $statusCode = $exception->getStatusCode();

            if ($statusCode >= 500) {
                $this->logger->critical($exception->getMessage(), ['exception' => $exception]);
            }

            $body = [
                'success' => false,
                'message' => $this->resolveCustomMessage($exception),
                'status' => $statusCode,
                'errorCode' => $exception->getErrorCode(),
            ];

            $errors = $exception->getOption(ErrorsOption::class);
            if (null !== $errors) {
                $body['errors'] = $this->resolveErrors($errors);
            }

            $event->setResponse(new JsonResponse($body, $statusCode));

            return;
        }

        if ($exception instanceof UnprocessableEntityHttpException
            && $exception->getPrevious() instanceof ValidationFailedException
        ) {
            $event->setResponse($this->buildValidationResponse($exception->getPrevious()));

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $message = $this->resolveMessage($exception, $statusCode);

            $context = ['exception' => $exception, 'path' => $event->getRequest()->getPathInfo()];
            $statusCode >= 500
                ? $this->logger->critical($message, $context)
                : $this->logger->warning($message, $context);

            $event->setResponse(new JsonResponse(
                [
                    'success' => false,
                    'message' => $message,
                    'status' => $statusCode,
                    'errorCode' => null,
                    'traces' => $this->debug ? $this->serializableTrace($exception) : [],
                ],
                $statusCode,
                $exception->getHeaders(),
            ));

            return;
        }

        $this->logger->critical($exception->getMessage(), [
            'exception' => $exception,
            'path' => $event->getRequest()->getPathInfo(),
        ]);

        $event->setResponse(new JsonResponse(
            [
                'success' => false,
                'message' => $this->trans('http.500'),
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'errorCode' => null,
            ],
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
    }

    /**
     * Resolves the final user-facing message for a domain exception (HttpStatusExceptionInterface).
     * Translates only if the exception carries a TranslationOption; otherwise returns the raw message.
     *
     * @param \Throwable&HttpStatusExceptionInterface $exception
     */
    private function resolveCustomMessage(HttpStatusExceptionInterface $exception): string
    {
        $translation = $exception->getOption(TranslationOption::class);

        if (null === $translation || !$translation->isTranslatable) {
            return $exception->getMessage();
        }

        return $this->translator->trans(
            $exception->getMessage(),
            $translation->parameters,
            domain: $translation->domain,
            locale: $this->localeResolver->resolve(),
        );
    }

    /**
     * @return array<string, list<string>>
     */
    private function resolveErrors(ErrorsOption $option): array
    {
        $locale = $this->localeResolver->resolve();
        $errors = [];

        foreach ($option->errors as $field => $messages) {
            foreach ($messages as $message) {
                $errors[$field][] = $message instanceof TranslatableInterface
                    ? $message->trans($this->translator, $locale)
                    : $message;
            }
        }

        return $errors;
    }

    /**
     * The debug-only trace, minus each frame's `args`. With zend.exception_ignore_args=0 those
     * args hold real objects (entities, UploadedFile, ...), and json_encode() aborts on the first
     * circular reference — turning any such 4xx into a 500 in dev. Location is what a trace is
     * read for anyway.
     *
     * @return list<array{file: string, line: int, function: string, class: string|null}>
     */
    private function serializableTrace(\Throwable $exception): array
    {
        return array_map(static fn (array $frame): array => [
            'file' => $frame['file'] ?? '',
            'line' => $frame['line'] ?? 0,
            'function' => $frame['function'] ?? '',
            'class' => $frame['class'] ?? null,
        ], $exception->getTrace());
    }

    private function buildValidationResponse(ValidationFailedException $e): JsonResponse
    {
        $errors = [];
        foreach ($e->getViolations() as $violation) {
            $field = ltrim($violation->getPropertyPath(), '.');
            $errors[$field][] = $violation->getMessage();
        }

        $status = $this->resolveValidationStatus($e->getViolations());

        [$message, $errorCode] = match ($status) {
            Response::HTTP_UNPROCESSABLE_ENTITY => [$this->trans('validation.failed'), null],
            Response::HTTP_CONFLICT => [$this->trans('validation.conflict'), 'CONFLICT'],
            default => [$this->trans("http.$status", 'http.default'), null],
        };

        return new JsonResponse(
            [
                'success' => false,
                'message' => $message,
                'status' => $status,
                'errorCode' => $errorCode,
                'errors' => $errors,
            ],
            $status,
        );
    }

    /**
     * The mapped status when every violation's constraint maps to the same status; 422 otherwise
     * (any unmapped violation, or violations mapped to different statuses).
     */
    private function resolveValidationStatus(ConstraintViolationListInterface $violations): int
    {
        $resolved = null;

        foreach ($violations as $violation) {
            $status = $this->statusForConstraint($violation->getConstraint());

            if (null === $status || (null !== $resolved && $resolved !== $status)) {
                return Response::HTTP_UNPROCESSABLE_ENTITY;
            }

            $resolved = $status;
        }

        return $resolved ?? Response::HTTP_UNPROCESSABLE_ENTITY;
    }

    /**
     * The status of the most specific mapped class the constraint is an instance of, so a project
     * mapping for a subclass wins over a default mapped for its parent, whatever the map order.
     */
    private function statusForConstraint(object|null $constraint): int|null
    {
        if (null === $constraint) {
            return null;
        }

        $matchedClass = null;

        foreach ($this->statusByConstraint as $class => $status) {
            // instanceof with a class-name string never autoloads: classes not installed are just false
            if ($constraint instanceof $class && (null === $matchedClass || is_a($class, $matchedClass, true))) {
                $matchedClass = $class;
            }
        }

        return null === $matchedClass ? null : $this->statusByConstraint[$matchedClass];
    }

    private function resolveMessage(HttpExceptionInterface $exception, int $statusCode): string
    {
        $msg = $exception->getMessage();

        // Symfony router/framework messages are not meaningful for API consumers
        $isFrameworkMessage = '' === $msg
            || str_starts_with($msg, 'No route found')
            || str_starts_with($msg, 'No message')
            || str_starts_with($msg, 'The controller must return');

        return $isFrameworkMessage
            ? $this->trans("http.$statusCode", 'http.default')
            : $msg;
    }

    private function trans(string $key, string|null $fallbackKey = null): string
    {
        $locale = $this->localeResolver->resolve();
        $translated = $this->translator->trans($key, domain: 'exceptions', locale: $locale);

        if ($translated === $key && null !== $fallbackKey) {
            return $this->translator->trans($fallbackKey, domain: 'exceptions', locale: $locale);
        }

        return $translated;
    }
}
