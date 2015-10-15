<?php

namespace Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher\ContentBased\Id;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\MVC\Symfony\Matcher\ViewMatcherInterface;
use Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher\ConfigResolverBased;
use eZ\Publish\Core\MVC\Symfony\View\ContentView;
use eZ\Publish\Core\MVC\Symfony\View\View;

class ParentContentType extends ConfigResolverBased implements ViewMatcherInterface
{
    /**
     * Checks if View object matches.
     *
     * @param \eZ\Publish\Core\MVC\Symfony\View\View $view
     *
     * @return bool
     */
    public function match( View $view )
    {
        if ( !$view instanceof ContentView )
        {
            return false;
        }

        $location = $view->getLocation();

        /** @var \eZ\Publish\API\Repository\Values\Content\Location $parent */
        $parent = $this->repository->sudo(
            function ( Repository $repository ) use ( $location )
            {
                return $repository->getLocationService()->loadLocation( $location->parentLocationId );
            }
        );

        return $this->doMatch( $parent->getContentInfo()->contentTypeId );
    }
}
