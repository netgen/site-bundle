<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\DependencyInjection\Compiler;

use Netgen\Bundle\MoreBundle\Debug\TwigTemplate;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TwigBaseTemplatePass implements CompilerPassInterface
{
    /**
     * Sets the debug variant of base template class to Twig environment.
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('twig') || !$container->getParameter('kernel.debug')) {
            return;
        }

        $container
            ->findDefinition('twig')
            ->addMethodCall('setBaseTemplateClass', [TwigTemplate::class]);
    }
}
