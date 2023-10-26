<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\DependencyInjection\Compiler;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Netgen\Bundle\SiteBundle\RichText\Converter\Link;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ServiceOverridesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definitionMapping = [
            'netgen.ibexa_site_api.ezrichtext.converter.link' => Link::class,
        ];

        foreach ($definitionMapping as $definitionIdentifier => $class) {
            if (!$container->hasDefinition($definitionIdentifier)) {
                continue;
            }

            $definition = $container->getDefinition($definitionIdentifier);

            /* @phpstan-ignore-next-line */
            if ($definitionIdentifier === 'netgen.ibexa_site_api.ezrichtext.converter.link') {
                $definition->setArgument('$configResolver', new Reference(ConfigResolverInterface::class));
            }

            $definition->setClass($class);
        }
    }
}
