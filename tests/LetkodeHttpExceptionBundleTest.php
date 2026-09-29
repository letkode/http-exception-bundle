<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Tests;

use Letkode\HttpExceptionBundle\Contract\LocaleResolverInterface;
use Letkode\HttpExceptionBundle\EventListener\ExceptionListener;
use Letkode\HttpExceptionBundle\LetkodeHttpExceptionBundle;
use Letkode\HttpExceptionBundle\Locale\RequestLocaleResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

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
}
