<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Publish\Core\MVC\Symfony\Controller\Content\ViewController as BaseViewController;

class ViewController extends BaseViewController
{
    /**
     * Main action for viewing content through a location in the repository.
     * Response will be cached with HttpCache validation model (Etag)
     *
     * @param int $locationId
     * @param string $viewType
     * @param boolean $layout
     * @param array $params
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @throws \Exception
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewLocation( $locationId, $viewType, $layout = false, array $params = array() )
    {
        return parent::viewLocation(
            $locationId,
            $viewType,
            $layout,
            $params + $this->container->get( 'netgen_more.component.page_layout' )->getParams( $locationId )
        );
    }
}
