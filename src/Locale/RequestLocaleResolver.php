<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Locale;

use Letkode\HttpExceptionBundle\Contract\LocaleResolverInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class RequestLocaleResolver implements LocaleResolverInterface
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function resolve(): string|null
    {
        return $this->requestStack->getCurrentRequest()?->getLocale();
    }
}
