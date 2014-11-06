<?php

namespace Netgen\Bundle\MoreBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Netgen\Bundle\MoreBundle\DependencyInjection\Compiler\XslRegisterPass;

class NetgenMoreBundle extends Bundle
{
    /**
     * Builds the bundle
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function build( ContainerBuilder $container )
    {
        parent::build( $container );
        $container->addCompilerPass( new XslRegisterPass() );
    }
}
