<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\DependencyInjection\Compiler;

use Netgen\Bundle\SiteBundle\Templating\Twig\Environment;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TwigEnvironmentPass implements CompilerPassInterface
{
    /**
     * Overrides the Twig environment to add path to template to rendered markup.
     */
    public function process(ContainerBuilder $container): void
    {
        if ($container->has('twig')) {
            $container
                ->findDefinition('twig')
                ->setClass(Environment::class);
        }
    }
}
