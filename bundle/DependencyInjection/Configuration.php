<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\DependencyInjection;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\Configuration as SiteAccessConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

use function is_string;

final class Configuration extends SiteAccessConfiguration
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ngsite');

        /** @var \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $this->generateScopeBaseNode($rootNode)
            ->arrayNode('showcase')
                ->children()
                    ->integerNode('rule_priority')
                        ->defaultValue(0)
                    ->end()
                    ->scalarNode('rule_group_uuid')
                        ->defaultValue('00000000-0000-0000-0000-000000000000')
                    ->end()
                    ->arrayNode('blocks')
                        ->useAttributeAsKey('block')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('title')
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('block_definition')
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('view_type')
                                    ->cannotBeEmpty()
                                ->end()
                                ->arrayNode('parameters')
                                    ->useAttributeAsKey('parameter')
                                    ->cannotBeEmpty()
                                    ->prototype('scalar')->end()
                                ->end()
                                ->arrayNode('item_view_types')
                                    ->useAttributeAsKey('item_view_type')
                                    ->cannotBeEmpty()
                                    ->prototype('scalar')
                                        ->validate()
                                            ->ifTrue(static fn (mixed $v): bool => !is_string($v))
                                            ->thenInvalid('Item view type name must be a string, %s given.')
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('variable_parameters')
                                    ->useAttributeAsKey('parameter')
                                    ->cannotBeEmpty()
                                    ->prototype('scalar')
                                        ->validate()
                                            ->ifTrue(static fn (mixed $v): bool => !is_string($v))
                                            ->thenInvalid('Parameter name must be a string, %s given.')
                                        ->end()
                                    ->end()
                                ->end()
                                ->booleanNode('use_view_content_types')
                                    ->defaultTrue()
                                ->end()
                                ->arrayNode('excluded_content_types')
                                    ->useAttributeAsKey('content_type')
                                    ->cannotBeEmpty()
                                    ->prototype('scalar')
                                        ->validate()
                                            ->ifTrue(static fn (mixed $v): bool => !is_string($v))
                                            ->thenInvalid('Content type must be a string, %s given.')
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('included_content_types')
                                    ->useAttributeAsKey('content_type')
                                    ->cannotBeEmpty()
                                    ->prototype('scalar')
                                        ->validate()
                                            ->ifTrue(static fn (mixed $v): bool => !is_string($v))
                                            ->thenInvalid('Content type must be a string, %s given.')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
