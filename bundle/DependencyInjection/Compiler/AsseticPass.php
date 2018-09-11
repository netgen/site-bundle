<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AsseticPass implements CompilerPassInterface
{
    /**
     * Removes the logging of template errors to console stdout in Assetic.
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('assetic.twig_formula_loader.real')) {
            return;
        }

        $container
            ->findDefinition('assetic.twig_formula_loader.real')
            ->replaceArgument(1, null);
    }
}
