<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Trait;

use Letkode\HttpExceptionBundle\Contract\ExceptionOptionInterface;

trait HttpStatusExceptionTrait
{
    /** @var array<class-string<ExceptionOptionInterface>, ExceptionOptionInterface> */
    private array $options = [];

    /**
     * @param list<ExceptionOptionInterface> $options
     */
    public function __construct(
        string $message,
        private readonly string|null $errorCode = null,
        \Throwable|null $previous = null,
        array $options = [],
    ) {
        parent::__construct($message, 0, $previous);

        foreach ($options as $option) {
            $this->options[$option::class] = $option;
        }
    }

    /**
     * @template T of ExceptionOptionInterface
     *
     * @param class-string<T> $optionClass
     *
     * @return T|null
     */
    public function getOption(string $optionClass): ExceptionOptionInterface|null
    {
        /** @var T|null $option */
        $option = $this->options[$optionClass] ?? null;

        return $option;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode ?? $this->defaultErrorCode();
    }

    abstract protected function defaultErrorCode(): string;
}
