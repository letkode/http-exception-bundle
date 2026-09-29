<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Tests\Locale;

use Letkode\HttpExceptionBundle\Contract\LocaleResolverInterface;
use Letkode\HttpExceptionBundle\Locale\RequestLocaleResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestLocaleResolverTest extends TestCase
{
    public function testItIsALocaleResolver(): void
    {
        self::assertInstanceOf(LocaleResolverInterface::class, new RequestLocaleResolver(new RequestStack()));
    }

    public function testReturnsNullWhenThereIsNoRequest(): void
    {
        self::assertNull(new RequestLocaleResolver(new RequestStack())->resolve());
    }

    public function testReturnsTheLocaleOfTheCurrentRequest(): void
    {
        $request = Request::create('/api/users');
        $request->setLocale('es');
        $stack = new RequestStack();
        $stack->push($request);

        self::assertSame('es', new RequestLocaleResolver($stack)->resolve());
    }

    public function testFollowsTheCurrentRequestOfTheStack(): void
    {
        $main = Request::create('/');
        $main->setLocale('es');
        $sub = Request::create('/sub');
        $sub->setLocale('fr');
        $stack = new RequestStack();
        $stack->push($main);
        $stack->push($sub);

        self::assertSame('fr', new RequestLocaleResolver($stack)->resolve());
    }
}
