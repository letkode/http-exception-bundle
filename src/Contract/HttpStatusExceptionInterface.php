<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Contract;

interface HttpStatusExceptionInterface
{
    public function getStatusCode(): int;

    public function getErrorCode(): string;

    /**
     * @template T of ExceptionOptionInterface
     *
     * @param class-string<T> $optionClass
     *
     * @return T|null
     */
    public function getOption(string $optionClass): ExceptionOptionInterface|null;
}
