<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Tests\Trait;

use Letkode\HttpExceptionBundle\Contract\ExceptionOptionInterface;
use Letkode\HttpExceptionBundle\Option\TranslationOption;
use Letkode\HttpExceptionBundle\Tests\Fixtures\StubHttpException;
use PHPUnit\Framework\TestCase;

final class HttpStatusExceptionTraitTest extends TestCase
{
    public function testErrorCodeDefaultsToTheExceptionDefault(): void
    {
        self::assertSame('STUB', (new StubHttpException('boom'))->getErrorCode());
    }

    public function testErrorCodeCanBeOverriddenByTheConstructor(): void
    {
        self::assertSame('CUSTOM', (new StubHttpException('boom', 'CUSTOM'))->getErrorCode());
    }

    public function testPreviousExceptionIsPreservedAndCodeIsZero(): void
    {
        $previous = new \LogicException('root cause');
        $exception = new StubHttpException('boom', previous: $previous);

        self::assertSame($previous, $exception->getPrevious());
        self::assertSame(0, $exception->getCode());
        self::assertSame('boom', $exception->getMessage());
    }

    public function testGetOptionReturnsTheRegisteredInstance(): void
    {
        $option = new TranslationOption(parameters: ['%name%' => 'Ana']);
        $exception = new StubHttpException('boom', options: [$option]);

        self::assertSame($option, $exception->getOption(TranslationOption::class));
    }

    public function testGetOptionReturnsNullWhenNotRegistered(): void
    {
        self::assertNull((new StubHttpException('boom'))->getOption(TranslationOption::class));
    }

    public function testLastOptionOfTheSameClassWins(): void
    {
        $first = new TranslationOption(domain: 'first');
        $second = new TranslationOption(domain: 'second');
        $exception = new StubHttpException('boom', options: [$first, $second]);

        self::assertSame($second, $exception->getOption(TranslationOption::class));
    }

    public function testOptionsOfOtherClassesAreKeptSeparately(): void
    {
        $other = new class implements ExceptionOptionInterface {
        };
        $translation = new TranslationOption();
        $exception = new StubHttpException('boom', options: [$other, $translation]);

        self::assertSame($other, $exception->getOption($other::class));
        self::assertSame($translation, $exception->getOption(TranslationOption::class));
    }

    public function testTranslationOptionDefaults(): void
    {
        $option = new TranslationOption();

        self::assertTrue($option->isTranslatable);
        self::assertSame('exceptions', $option->domain);
        self::assertSame([], $option->parameters);
    }
}
