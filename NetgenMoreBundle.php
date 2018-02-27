<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle;

use Netgen\Bundle\MoreBundle\DependencyInjection\Compiler;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NetgenMoreBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new Compiler\XslRegisterPass());
        $container->addCompilerPass(new Compiler\RelationListFieldTypePass());
        $container->addCompilerPass(new Compiler\XmlTextFieldTypePass());
        $container->addCompilerPass(new Compiler\ImagineIOResolverPass());
        $container->addCompilerPass(new Compiler\ContentDownloadUrlGeneratorPass());
        $container->addCompilerPass(new Compiler\LocationFactoryPass());
    }
}
