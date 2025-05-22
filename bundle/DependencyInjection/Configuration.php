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
        $treeBuilder = new TreeBuilder('netgen_site');

        /** @var \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $this->generateScopeBaseNode($rootNode)
            ->arrayNode('showcase')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('blocks')
                        ->useAttributeAsKey('block')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('title')
                                    ->cannotBeEmpty()
                                    ->defaultValue('%%viewType%% block (%%itemViewType%% view) %%parameters%%')
                                ->end()
                                ->scalarNode('block_definition')
                                    ->cannotBeEmpty()
                                    ->defaultValue('list')
                                ->end()
                                ->scalarNode('view_type')
                                    ->cannotBeEmpty()
                                    ->defaultValue('list')
                                ->end()
                                ->arrayNode('parameters')
                                    ->useAttributeAsKey('parameter')
                                    ->defaultValue([])
                                    ->prototype('scalar')->end()
                                ->end()
                                ->arrayNode('item_view_types')
                                    ->useAttributeAsKey('item_view_type')
                                    ->defaultValue([])
                                    ->prototype('scalar')
                                        ->validate()
                                            ->ifTrue(static fn (mixed $v): bool => !is_string($v))
                                            ->thenInvalid('Item view type name must be a string, %s given.')
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('variable_parameters')
                                    ->useAttributeAsKey('parameter')
                                    ->defaultValue([])
                                    ->prototype('scalar')
                                        ->validate()
                                            ->ifTrue(static fn (mixed $v): bool => !is_string($v))
                                            ->thenInvalid('Parameter name must be a string, %s given.')
                                        ->end()
                                    ->end()
                                ->end()
                                ->booleanNode('ignore_view_content_types')
                                    ->defaultFalse()
                                ->end()
                                ->arrayNode('excluded_content_types')
                                    ->useAttributeAsKey('content_type')
                                    ->defaultValue([])
                                    ->prototype('scalar')
                                        ->validate()
                                            ->ifTrue(static fn (mixed $v): bool => !is_string($v))
                                            ->thenInvalid('Content type must be a string, %s given.')
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('included_content_types')
                                    ->useAttributeAsKey('content_type')
                                    ->defaultValue([])
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
