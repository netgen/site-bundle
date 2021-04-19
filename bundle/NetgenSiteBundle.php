<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle;

use Netgen\Bundle\SiteBundle\DependencyInjection\Compiler;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NetgenSiteBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new Compiler\XslRegisterPass());
        $container->addCompilerPass(new Compiler\RelationListFieldTypePass());
        $container->addCompilerPass(new Compiler\XmlTextFieldTypePass());
        $container->addCompilerPass(new Compiler\ImagineIOResolverPass());
        $container->addCompilerPass(new Compiler\ContentDownloadUrlGeneratorPass());
        $container->addCompilerPass(new Compiler\LocationFactoryPass());
        $container->addCompilerPass(new Compiler\AsseticPass());
        $container->addCompilerPass(new Compiler\IoStorageAllowListPass());
    }
}
