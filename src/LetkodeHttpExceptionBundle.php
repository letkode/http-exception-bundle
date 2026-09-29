<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle;

use Letkode\HttpExceptionBundle\EventListener\ExceptionListener;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class LetkodeHttpExceptionBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('path_prefix')
                    ->defaultValue('/api')
                    ->validate()
                        ->ifTrue(static fn (mixed $v): bool => !\is_string($v))
                        ->thenInvalid('path_prefix must be a string.')
                    ->end()
                ->end()
                ->booleanNode('listener_enabled')->defaultTrue()->end()
                ->integerNode('listener_priority')->defaultValue(0)->end()
            ->end()
        ;
    }

    /**
     * @param array{path_prefix: string, listener_enabled: bool, listener_priority: int} $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import($this->getPath() . '/config/services.yaml');

        if (!$config['listener_enabled']) {
            $builder->removeDefinition(ExceptionListener::class);
        }

        $builder->setParameter('letkode.http_exception.path_prefix', $config['path_prefix']);
        $builder->setParameter('letkode.http_exception.listener_priority', $config['listener_priority']);
    }
}
