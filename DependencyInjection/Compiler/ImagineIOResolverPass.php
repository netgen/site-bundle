<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\DependencyInjection\Compiler;

use Netgen\Bundle\MoreBundle\Imagine\IORepositoryResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ImagineIOResolverPass implements CompilerPassInterface
{
    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->has('ezpublish.image_alias.imagine.cache_resolver')) {
            $container
                ->findDefinition('ezpublish.image_alias.imagine.cache_resolver')
                ->setClass(IORepositoryResolver::class);
        }
    }
}
