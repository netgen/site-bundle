<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\DependencyInjection\Compiler;

use Ibexa\Core\MVC\Symfony\FieldType\BinaryBase\ContentDownloadUrlGenerator as BaseContentDownloadUrlGenerator;
use Netgen\Bundle\SiteBundle\Core\MVC\Symfony\FieldType\BinaryBase\ContentDownloadUrlGenerator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContentDownloadUrlGeneratorPass implements CompilerPassInterface
{
    /**
     * Override content download URL generator to generate download links
     * to files with Netgen Site download route.
     */
    public function process(ContainerBuilder $container): void
    {
        if ($container->has(BaseContentDownloadUrlGenerator::class)) {
            $container
                ->findDefinition(BaseContentDownloadUrlGenerator::class)
                ->setClass(ContentDownloadUrlGenerator::class);
        }
    }
}
