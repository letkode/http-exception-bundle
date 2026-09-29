<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Tests\Fixtures;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class StubTranslatable implements TranslatableInterface
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        private string $id,
        private array $parameters = [],
        private string $domain = 'exceptions',
    ) {
    }

    public function trans(TranslatorInterface $translator, string|null $locale = null): string
    {
        return $translator->trans($this->id, $this->parameters, $this->domain, $locale);
    }
}
