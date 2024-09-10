<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\Imagine\VariationPathGenerator\WebpFormatVariationPathGenerator as BaseWebpFormatVariationPathGenerator;
use Netgen\Bundle\SiteBundle\Core\Imagine\VariationPathGenerator\WebpFormatVariationPathGenerator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function array_keys;
use function in_array;
use function is_array;

class WebpFormatVariationPathGeneratorDecoratorPass implements CompilerPassInterface
{
    /**
     * Overrides default Webp image alias variation path generator decorator to comply with legacy variation URL pattern
     * We do this only if we have Netgen AdminUI installed (legacy-based administration).
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('kernel.bundles') || !is_array($container->getParameter('kernel.bundles'))) {
            return;
        }

        $activatedBundles = array_keys($container->getParameter('kernel.bundles'));

        if (!in_array('NetgenAdminUIBundle', $activatedBundles, true)) {
            return;
        }

        if (!$container->has(BaseWebpFormatVariationPathGenerator::class)) {
            return;
        }

        $container
            ->findDefinition(BaseWebpFormatVariationPathGenerator::class)
            ->setClass(WebpFormatVariationPathGenerator::class);
    }
}
