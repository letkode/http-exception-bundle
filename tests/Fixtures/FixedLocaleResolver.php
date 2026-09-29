<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Tests\Fixtures;

use Letkode\HttpExceptionBundle\Contract\LocaleResolverInterface;

final readonly class FixedLocaleResolver implements LocaleResolverInterface
{
    public function __construct(private string|null $locale)
    {
    }

    public function resolve(): string|null
    {
        return $this->locale;
    }
}
