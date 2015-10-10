<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller\PageController as BasePageController;
use eZ\Publish\Core\FieldType\Page\Parts\Block;

class PageController extends BasePageController
{
    /**
     * Render the block
     *
     * @param \eZ\Publish\Core\FieldType\Page\Parts\Block $block
     * @param array $params
     * @param array $cacheSettings settings for the HTTP cache, 'smax-age' and
     *        'max-age' are checked.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewBlock( Block $block, array $params = array(), array $cacheSettings = array() )
    {
        $configResolver = $this->getConfigResolver();
        $blockType = strtolower( $block->type );

        if ( !isset( $cacheSettings['smax-age'] ) )
        {
            if ( $configResolver->hasParameter( 'block_settings.' . $blockType . '.shared_max_age', 'ngmore' ) )
            {
                $cacheSettings['smax-age'] = (int)$configResolver->getParameter( 'block_settings.' . $blockType . '.shared_max_age', 'ngmore' );
            }
        }

        if ( !isset( $cacheSettings['max-age'] ) )
        {
            if ( $configResolver->hasParameter( 'block_settings.' . $blockType . '.max_age', 'ngmore' ) )
            {
                $cacheSettings['max-age'] = (int)$configResolver->getParameter( 'block_settings.' . $blockType . '.max_age', 'ngmore' );
            }
        }

        return parent::viewBlock( $block, $params, $cacheSettings );
    }
}
