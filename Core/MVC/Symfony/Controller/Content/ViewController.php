<?php

namespace Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Controller\Content;

use eZ\Publish\Core\MVC\Symfony\Controller\Content\ViewController as BaseViewController;
use eZ\Publish\API\Repository\Repository;

class ViewController extends BaseViewController
{
    /**
     * Main action for viewing content through a location in the repository.
     * Response will be cached with HttpCache validation model (Etag).
     *
     * @param int $locationId
     * @param string $viewType
     * @param bool $layout
     * @param array $params
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @throws \Exception
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewLocation($locationId, $viewType, $layout = false, array $params = array())
    {
        $showInvisibleLocations = false;
        if ($this->getConfigResolver()->hasParameter('content_view.show_invisible_locations', 'ngmore')) {
            $showInvisibleLocations = (bool)$this->getConfigResolver()->getParameter(
                'content_view.show_invisible_locations',
                'ngmore'
            );
        }

        if ($showInvisibleLocations) {
            $location = $this->getRepository()->getLocationService()->loadLocation($locationId);
            $params = array('location' => $location) + $params;
        }

        return parent::viewLocation($locationId, $viewType, $layout, $params);
    }
}
