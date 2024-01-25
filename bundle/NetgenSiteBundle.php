<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle;

use Netgen\Bundle\SiteBundle\DependencyInjection\Compiler;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class NetgenSiteBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new Compiler\XslRegisterPass());
        $container->addCompilerPass(new Compiler\ImagineIOResolverPass());
        $container->addCompilerPass(new Compiler\ContentDownloadUrlGeneratorPass());
        $container->addCompilerPass(new Compiler\TwigEnvironmentPass());
        $container->addCompilerPass(new Compiler\IoStorageAllowListPass());
        $container->addCompilerPass(new Compiler\PHPStormPass(), PassConfig::TYPE_OPTIMIZE);
        $container->addCompilerPass(new Compiler\DirectDownloadPass());
    }
}
