<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\DependencyInjection\Compiler;

use Netgen\Bundle\SiteBundle\Core\Imagine\VariationPathGenerator\WebpFormatVariationPathGenerator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WebpFormatVariationPathGeneratorDecoratorPass implements CompilerPassInterface
{
    /**
     * Overrides the IO resolver to disable generating absolute URIs to images.
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('ezpublish.image_alias.variation_path_generator')) {
            return;
        }

        $container->register(
            'ezpublish.image_alias.webp_variation_path_generator_decorator',
            WebpFormatVariationPathGenerator::class,
        )
            ->setDecoratedService('ezpublish.image_alias.variation_path_generator')
            ->addArgument(new Reference('ezpublish.image_alias.webp_variation_path_generator_decorator.inner'))
            ->addArgument(new Reference('liip_imagine.filter.configuration'));
    }
}
