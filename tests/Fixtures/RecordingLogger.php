<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Tests\Fixtures;

use Psr\Log\AbstractLogger;

final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{level: string, message: string, context: array<mixed>}> */
    public array $records = [];

    /**
     * @param array<mixed> $context
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->records[] = ['level' => \is_string($level) ? $level : '', 'message' => (string) $message, 'context' => $context];
    }

    /**
     * @return list<array{level: string, message: string, context: array<mixed>}>
     */
    public function withLevel(string $level): array
    {
        return array_values(array_filter($this->records, static fn (array $record): bool => $record['level'] === $level));
    }
}
