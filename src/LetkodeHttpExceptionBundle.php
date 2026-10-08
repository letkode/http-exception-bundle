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
    /**
     * Constraint FQCNs (plain strings — no dependency on the packages that define them) whose
     * violations turn a validation failure into the given status when every violation maps to it.
     * Projects add entries or disable one with null via `validation.status_by_constraint`.
     */
    public const array DEFAULT_STATUS_BY_CONSTRAINT = [
        'Letkode\CommonBundle\Attribute\Constraint\UniqueField\UniqueField' => 409,
        'Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity' => 409,
    ];

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
                ->arrayNode('validation')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('status_by_constraint')
                            ->normalizeKeys(false)
                            ->useAttributeAsKey('class')
                            ->variablePrototype()
                                ->validate()
                                    ->ifTrue(static fn (mixed $v): bool => null !== $v && (!\is_int($v) || $v < 400 || $v > 599))
                                    ->thenInvalid('status_by_constraint values must be an HTTP status between 400 and 599, or null to disable a default; got %s.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * @param array{path_prefix: string, listener_enabled: bool, listener_priority: int, validation: array{status_by_constraint: array<string, int|null>}} $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import($this->getPath() . '/config/services.yaml');

        if (!$config['listener_enabled']) {
            $builder->removeDefinition(ExceptionListener::class);
        } else {
            $statusByConstraint = array_filter(
                array_replace(self::DEFAULT_STATUS_BY_CONSTRAINT, $config['validation']['status_by_constraint']),
                static fn (int|null $status): bool => null !== $status,
            );

            $builder->getDefinition(ExceptionListener::class)->setArgument('$statusByConstraint', $statusByConstraint);
        }

        $builder->setParameter('letkode.http_exception.path_prefix', $config['path_prefix']);
        $builder->setParameter('letkode.http_exception.listener_priority', $config['listener_priority']);
    }
}
