<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\DependencyInjection\Compiler;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Netgen\Bundle\SiteBundle\RichText\Converter\Link;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class DirectDownloadPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->has('netgen.ibexa_site_api.ezrichtext.converter.link')) {
            $container
                ->findDefinition('netgen.ibexa_site_api.ezrichtext.converter.link')
                ->setArgument('$configResolver', new Reference(ConfigResolverInterface::class))
                ->setClass(Link::class);
        }
    }
}
