<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Option;

use Letkode\HttpExceptionBundle\Contract\ExceptionOptionInterface;

final readonly class TranslationOption implements ExceptionOptionInterface
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        public bool $isTranslatable = true,
        public string $domain = 'exceptions',
        public array $parameters = [],
    ) {
    }
}
