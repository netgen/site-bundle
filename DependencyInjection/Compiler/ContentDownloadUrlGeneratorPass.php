<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\DependencyInjection\Compiler;

use Netgen\Bundle\MoreBundle\Core\MVC\Symfony\FieldType\BinaryBase\ContentDownloadUrlGenerator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContentDownloadUrlGeneratorPass implements CompilerPassInterface
{
    /**
     * Override content download URL generator to generate download links
     * to files with Netgen More download route.
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->has('ezpublish.fieldType.ezbinarybase.download_url_generator')) {
            $container
                ->findDefinition('ezpublish.fieldType.ezbinarybase.download_url_generator')
                ->setClass(ContentDownloadUrlGenerator::class);
        }
    }
}
