<?php

namespace Netgen\Bundle\MoreBundle\DependencyInjection\Compiler;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RemoveDefaultImageAliasesPass implements CompilerPassInterface
{
    /**
     * Compiler pass to remove default eZ Publish image aliases.
     *
     * It removes image alias called reference, as well as all image aliases which
     * reference points to "reference" image alias.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function process( ContainerBuilder $container )
    {
        $scopes = array_merge(
            array( ConfigResolver::SCOPE_DEFAULT ),
            $container->getParameter( 'ezpublish.siteaccess.list' )
        );

        foreach ( $scopes as $scope )
        {
            if ( !$container->hasParameter( "ezsettings.$scope.image_variations" ) )
            {
                continue;
            }

            $imageVariations = $container->getParameter( "ezsettings.$scope.image_variations" );

            unset($imageVariations["reference"]);
            foreach ( $imageVariations as $variationName => $variation )
            {
                if ( $variation["reference"] === "reference" )
                {
                    unset( $imageVariations[$variationName] );
                }
            }

            $container->setParameter( "ezsettings.$scope.image_variations", $imageVariations );
        }
    }
}
