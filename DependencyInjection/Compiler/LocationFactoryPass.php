<?php

namespace Netgen\Bundle\MoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class LocationFactoryPass implements CompilerPassInterface
{
    /**
     * Injects location factory extensions into the factory.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('ngmore.menu.factory.location')) {
            return;
        }

        $factory = $container->findDefinition('ngmore.menu.factory.location');

        $extensions = array();

        foreach ($container->findTaggedServiceIds('ngmore.menu.factory.location.extension') as $extension => $tags) {
            foreach ($tags as $tag) {
                $priority = isset($tag[0]['priority']) ? (int) $tag[0]['priority'] : 0;
                $extensions[$priority][] = new Reference($extension);
            }
        }

        krsort($extensions);
        $extensions = array_merge(...$extensions);

        $factory->replaceArgument(1, $extensions);
    }
}
