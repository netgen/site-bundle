<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\DependencyInjection\Compiler;

use Netgen\Bundle\SiteBundle\Imagine\IORepositoryResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ImagineIOResolverPass implements CompilerPassInterface
{
    /**
     * Overrides the IO resolver to disable generating absolute URIs to images.
     */
    public function process(ContainerBuilder $container): void
    {
        if ($container->has('ezpublish.image_alias.imagine.cache_resolver')) {
            $container
                ->findDefinition('ezpublish.image_alias.imagine.cache_resolver')
                ->setClass(IORepositoryResolver::class);
        }
    }
}
