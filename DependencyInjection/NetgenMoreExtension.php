<?php

namespace Netgen\Bundle\MoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class NetgenMoreExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function load( array $configs, ContainerBuilder $container )
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration( $configuration, $configs );

        $loader = new Loader\YamlFileLoader( $container, new FileLocator( __DIR__ . '/../Resources/config' ) );

        $loader->load( 'field_types.yml' );
        $loader->load( 'pagerfanta.yml' );
        $loader->load( 'templating.yml' );
        $loader->load( 'kernel.yml' );
        $loader->load( 'menu.yml' );
        $loader->load( 'event_listeners.yml' );
        $loader->load( 'matchers.yml' );
        $loader->load( 'services.yml' );
    }

    /**
     * Allow an extension to prepend the extension configurations.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function prepend( ContainerBuilder $container )
    {
        $container->prependExtensionConfig(
            'assetic',
            array(
                'bundles' => array_keys(
                    $container->getParameter( 'kernel.bundles' )
                )
            )
        );
    }
}
