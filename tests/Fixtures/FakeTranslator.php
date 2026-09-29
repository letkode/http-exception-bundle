<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Tests\Fixtures;

use Symfony\Contracts\Translation\TranslatorInterface;

final class FakeTranslator implements TranslatorInterface
{
    /** @var list<array{id: string, parameters: array<string, mixed>, domain: string|null, locale: string|null}> */
    public array $calls = [];

    /**
     * @param array<string, string> $messages keyed by "<domain>|<id>"
     */
    public function __construct(private readonly array $messages = [])
    {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $this->calls[] = ['id' => $id, 'parameters' => $parameters, 'domain' => $domain, 'locale' => $locale];

        return $this->messages[($domain ?? 'messages') . '|' . $id] ?? $id;
    }

    public function getLocale(): string
    {
        return 'en';
    }
}
