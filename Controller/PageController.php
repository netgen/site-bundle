<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Publish\Core\MVC\Symfony\Controller\PageController as BasePageController;

class PageController extends BasePageController
{
    /**
     * Renders the block with given $id.
     *
     * This method can be used with ESI rendering strategy.
     *
     * @uses self::viewBlock()
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException If block could not be found.
     *
     * @param mixed $id Block id
     * @param array $params
     * @param array $cacheSettings settings for the HTTP cache, 'smax-age' and
     *              'max-age' are checked.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewBlockById( $id, array $params = array(), array $cacheSettings = array() )
    {
        $block = $this->pageService->loadBlock( $id );
        $configResolver = $this->getConfigResolver();

        if ( !isset( $cacheSettings['smax-age'] ) )
        {
            if ( $configResolver->hasParameter( 'BlockSettings.' . $block->type . '.TTL', 'ngmore' ) )
            {
                $cacheSettings['smax-age'] = (int)$configResolver->getParameter( 'BlockSettings.' . $block->type . '.TTL', 'ngmore' );
            }
        }

        $response = $this->viewBlock( $block, $params, $cacheSettings );

        if ( isset( $block->customAttributes['parent_node'] ) )
        {
            $response->headers->set( 'X-Location-Id', (int)$block->customAttributes['parent_node'] );
        }

        return $response;
    }
}
