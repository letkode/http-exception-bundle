<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Tests;

use Letkode\HttpExceptionBundle\Contract\LocaleResolverInterface;
use Letkode\HttpExceptionBundle\EventListener\ExceptionListener;
use Letkode\HttpExceptionBundle\LetkodeHttpExceptionBundle;
use Letkode\HttpExceptionBundle\Locale\RequestLocaleResolver;
use Letkode\HttpExceptionBundle\Tests\Fixtures\FakeTranslator;
use Letkode\HttpExceptionBundle\Tests\Fixtures\RecordingLogger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LetkodeHttpExceptionBundleTest extends TestCase
{
    /**
     * @param array<string, mixed> $config
     */
    private function load(array $config = []): ContainerBuilder
    {
        $container = new ContainerBuilder(new ParameterBag(['kernel.debug' => true]));
        $extension = new LetkodeHttpExceptionBundle()->getContainerExtension();
        self::assertNotNull($extension);

        $extension->load([$config], $container);

        return $container;
    }

    public function testExtensionAliasAndBundlePath(): void
    {
        $bundle = new LetkodeHttpExceptionBundle();

        self::assertSame('letkode_http_exception', $bundle->getContainerExtension()?->getAlias());
        self::assertFileExists($bundle->getPath() . '/config/services.yaml');
        self::assertDirectoryExists($bundle->getPath() . '/translations');
    }

    public function testDefaultConfiguration(): void
    {
        $container = $this->load();

        self::assertSame('/api', $container->getParameter('letkode.http_exception.path_prefix'));
        self::assertSame(0, $container->getParameter('letkode.http_exception.listener_priority'));
    }

    public function testCustomConfiguration(): void
    {
        $container = $this->load(['path_prefix' => '/v1', 'listener_priority' => -10]);

        self::assertSame('/v1', $container->getParameter('letkode.http_exception.path_prefix'));
        self::assertSame(-10, $container->getParameter('letkode.http_exception.listener_priority'));
    }

    public function testListenerIsWiredWithDebugAndPathPrefix(): void
    {
        $definition = $this->load()->getDefinition(ExceptionListener::class);

        self::assertSame('%kernel.debug%', $definition->getArgument('$debug'));
        self::assertSame('%letkode.http_exception.path_prefix%', $definition->getArgument('$pathPrefix'));
    }

    public function testListenerIsTaggedOnKernelExceptionWithConfiguredPriority(): void
    {
        $container = $this->load(['listener_priority' => -10]);
        $tags = $container->getDefinition(ExceptionListener::class)->getTag('kernel.event_listener');

        self::assertCount(1, $tags);
        self::assertSame('kernel.exception', $tags[0]['event']);
        self::assertSame('__invoke', $tags[0]['method']);
        self::assertSame(-10, $container->getParameterBag()->resolveValue($tags[0]['priority']));
    }

    public function testLocaleResolverAliasPointsToTheRequestResolverAndCanBeReplaced(): void
    {
        $container = $this->load();

        self::assertTrue($container->hasDefinition(RequestLocaleResolver::class));
        self::assertSame(
            RequestLocaleResolver::class,
            (string) $container->getAlias(LocaleResolverInterface::class),
        );

        $container->setAlias(LocaleResolverInterface::class, 'app.custom_locale_resolver');
        self::assertSame('app.custom_locale_resolver', (string) $container->getAlias(LocaleResolverInterface::class));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidPathPrefixProvider(): iterable
    {
        yield 'integer' => [123];
        yield 'null' => [null];
    }

    #[DataProvider('invalidPathPrefixProvider')]
    public function testNonStringPathPrefixIsRejected(mixed $value): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->load(['path_prefix' => $value]);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, int}>
     */
    public static function priorityProvider(): iterable
    {
        yield 'default' => [[], 0];
        yield 'custom' => [['listener_priority' => -10], -10];
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('priorityProvider')]
    public function testContainerCompilesAndRegistersTheListenerWithConfiguredPriority(array $config, int $expected): void
    {
        $container = $this->load($config);
        $container->register('event_dispatcher', EventDispatcher::class)->setPublic(true);
        $container->register('translator', FakeTranslator::class)->setPublic(true);
        $container->setAlias(TranslatorInterface::class, 'translator');
        $container->register('logger', RecordingLogger::class)->setPublic(true);
        $container->setAlias(LoggerInterface::class, 'logger');
        $container->register('request_stack', RequestStack::class)->setPublic(true);
        $container->setAlias(RequestStack::class, 'request_stack');
        $container->addCompilerPass(new RegisterListenersPass());

        $container->compile();

        $found = false;
        foreach ($container->getDefinition('event_dispatcher')->getMethodCalls() as [$method, $arguments]) {
            if ('addListener' === $method && 'kernel.exception' === $arguments[0]) {
                $found = true;
                self::assertSame($expected, $container->getParameterBag()->resolveValue($arguments[2]));
            }
        }

        self::assertTrue($found, 'No addListener call for kernel.exception was registered.');
    }

    public function testListenerIsEnabledByDefault(): void
    {
        self::assertTrue($this->load()->hasDefinition(ExceptionListener::class));
        self::assertTrue($this->load(['listener_enabled' => true])->hasDefinition(ExceptionListener::class));
    }

    public function testListenerCanBeDisabled(): void
    {
        $container = $this->load(['listener_enabled' => false, 'path_prefix' => '/v1']);

        self::assertFalse($container->hasDefinition(ExceptionListener::class));
        // Everything else the bundle provides is still registered.
        self::assertTrue($container->hasDefinition(RequestLocaleResolver::class));
        self::assertTrue($container->hasAlias(LocaleResolverInterface::class));
        self::assertSame('/v1', $container->getParameter('letkode.http_exception.path_prefix'));
    }

    public function testDisabledListenerIsNotRegisteredOnTheDispatcher(): void
    {
        $container = $this->load(['listener_enabled' => false]);

        $container->register('event_dispatcher', EventDispatcher::class)->setPublic(true);
        $container->addCompilerPass(new RegisterListenersPass());
        $container->compile();

        self::assertSame([], $container->getDefinition('event_dispatcher')->getMethodCalls());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidListenerEnabledProvider(): iterable
    {
        yield 'string' => ['yes'];
        yield 'integer' => [1];
    }

    #[DataProvider('invalidListenerEnabledProvider')]
    public function testNonBooleanListenerEnabledIsRejected(mixed $value): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->load(['listener_enabled' => $value]);
    }
}
