<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Contract;

interface LocaleResolverInterface
{
    /**
     * The locale used to translate error messages, or null to let the translator use its own.
     */
    public function resolve(): string|null;
}
