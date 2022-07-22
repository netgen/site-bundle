<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use function array_merge;
use function krsort;

class LocationFactoryPass implements CompilerPassInterface
{
    /**
     * Injects location factory extensions into the factory.
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('ngsite.menu.factory.location')) {
            return;
        }

        $factory = $container->findDefinition('ngsite.menu.factory.location');

        $extensions = [];

        foreach ($container->findTaggedServiceIds('ngsite.menu.factory.location.extension') as $extension => $tags) {
            foreach ($tags as $tag) {
                $priority = (int) ($tag[0]['priority'] ?? 0);
                $extensions[$priority][] = new Reference($extension);
            }
        }

        krsort($extensions);
        $extensions = array_merge(...$extensions);

        $factory->replaceArgument(2, $extensions);
    }
}
