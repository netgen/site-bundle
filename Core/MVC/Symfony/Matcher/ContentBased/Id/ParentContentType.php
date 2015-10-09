<?php

namespace Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher\ContentBased\Id;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\Location as APILocation;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\Core\MVC\Symfony\Matcher\ContentBased\MatcherInterface;
use Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher\ConfigResolverBased;
use eZ\Publish\Core\MVC\Symfony\View\ContentView;
use eZ\Publish\Core\MVC\Symfony\View\View;

class ParentContentType extends ConfigResolverBased implements MatcherInterface
{
    /**
     * Checks if a Location object matches.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     *
     * @return bool
     */
    public function matchLocation( APILocation $location )
    {
        $parent = $this->repository->sudo(
            function ( Repository $repository ) use ( $location )
            {
                return $repository->getLocationService()->loadLocation( $location->parentLocationId );
            }
        );

        return $this->doMatch( $parent->getContentInfo()->contentTypeId );
    }

    /**
     * Checks if a ContentInfo object matches.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\ContentInfo $contentInfo
     *
     * @return bool
     */
    public function matchContentInfo( ContentInfo $contentInfo )
    {
        $location = $this->repository->sudo(
            function ( Repository $repository ) use ( $contentInfo )
            {
                return $repository->getLocationService()->loadLocation( $contentInfo->mainLocationId );
            }
        );

        return $this->matchLocation( $location );
    }

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

        return $this->matchLocation( $view->getLocation() );
    }
}
