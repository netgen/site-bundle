<?php

namespace Netgen\Bundle\MoreBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Netgen\Bundle\MoreBundle\DependencyInjection\Compiler;

class NetgenMoreBundle extends Bundle
{
    /**
     * Builds the bundle.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new Compiler\XslRegisterPass());
        $container->addCompilerPass(new Compiler\RelationListFieldTypePass());
        $container->addCompilerPass(new Compiler\XmlTextFieldTypePass());
        $container->addCompilerPass(new Compiler\AsseticFilterPass());
        $container->addCompilerPass(new Compiler\ImagineIOResolverPass());
    }
}
