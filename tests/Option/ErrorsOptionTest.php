<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Tests\Option;

use Letkode\HttpExceptionBundle\Contract\ExceptionOptionInterface;
use Letkode\HttpExceptionBundle\Exception\UnprocessableEntityException;
use Letkode\HttpExceptionBundle\Option\ErrorsOption;
use Letkode\HttpExceptionBundle\Tests\Fixtures\StubTranslatable;
use PHPUnit\Framework\TestCase;

final class ErrorsOptionTest extends TestCase
{
    public function testItIsAnExceptionOption(): void
    {
        self::assertInstanceOf(ExceptionOptionInterface::class, new ErrorsOption([]));
    }

    public function testItKeepsTheErrorsByField(): void
    {
        $translatable = new StubTranslatable('errors.too_short');
        $option = new ErrorsOption(['name' => ['Required.', $translatable], 'items[0].sku' => ['Invalid.']]);

        self::assertSame(['name' => ['Required.', $translatable], 'items[0].sku' => ['Invalid.']], $option->errors);
    }

    public function testAnExceptionExposesItThroughGetOption(): void
    {
        $option = new ErrorsOption(['name' => ['Required.']]);
        $exception = new UnprocessableEntityException('Invalid.', options: [$option]);

        self::assertSame($option, $exception->getOption(ErrorsOption::class));
    }
}
