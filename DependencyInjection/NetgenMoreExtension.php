<?php

namespace Netgen\Bundle\MoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class NetgenMoreExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load( array $configs, ContainerBuilder $container )
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration( $configuration, $configs );

        $loader = new Loader\YamlFileLoader( $container, new FileLocator( __DIR__ . '/../Resources/config' ) );

        $loader->load( 'parameters.yml' );
        $loader->load( 'fieldtypes.yml' );
        $loader->load( 'roles.yml' );
        $loader->load( 'services.yml' );
        $loader->load( 'twig_services.yml' );

        $this->injectBlockMatchCustomControllers( $container );
    }

    /**
     * Injects custom controllers to block view match config, used by overriden controller manager
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function injectBlockMatchCustomControllers( ContainerBuilder $container )
    {
        $siteAccessGroups = $container->getParameter( 'ezpublish.siteaccess.groups' );

        if ( $container->hasParameter( 'ngmore.block_view' ) )
        {
            $newBlockViewConfig = $container->getParameter( 'ngmore.block_view' );
            foreach ( $newBlockViewConfig as $siteAccess => $matchList )
            {
                if ( $container->hasParameter( 'ezsettings.' . $siteAccess . '.block_view' ) )
                {
                    $originalBlockViewConfig = $container->getParameter( 'ezsettings.' . $siteAccess . '.block_view' );

                    $container->setParameter(
                        'ezsettings.' . $siteAccess . '.block_view',
                        array_merge_recursive( $originalBlockViewConfig, array( 'block' => $matchList ) )
                    );
                }
                else if ( isset( $siteAccessGroups[$siteAccess] ) )
                {
                    foreach ( $siteAccessGroups[$siteAccess] as $groupSiteAccess )
                    {
                        if ( $container->hasParameter( 'ezsettings.' . $groupSiteAccess . '.block_view' ) )
                        {
                            $originalBlockViewConfig = $container->getParameter( 'ezsettings.' . $groupSiteAccess . '.block_view' );

                            $container->setParameter(
                                'ezsettings.' . $groupSiteAccess . '.block_view',
                                array_merge_recursive( $originalBlockViewConfig, array( 'block' => $matchList ) )
                            );
                        }
                    }
                }
            }
        }
    }
}
