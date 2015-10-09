<?php

namespace Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher\ContentBased\Id;

use eZ\Publish\API\Repository\Values\Content\Location as APILocation;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\Core\MVC\Symfony\Matcher\ContentBased\MatcherInterface;
use Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher\ConfigResolverBased;
use eZ\Publish\Core\MVC\Symfony\View\ContentView;
use eZ\Publish\Core\MVC\Symfony\View\View;

class ContentTypeGroup extends ConfigResolverBased implements MatcherInterface
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
        return $this->matchContentTypeId( $location->getContentInfo()->contentTypeId );
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
        return $this->matchContentTypeId( $contentInfo->contentTypeId );
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

        return $this->matchContentTypeId( $view->getContent()->contentInfo->contentTypeId );
    }

    /**
     * Checks if a content type ID matches.
     *
     * @param mixed $contentTypeId
     *
     * @return bool
     */
    protected function matchContentTypeId( $contentTypeId )
    {
        $contentTypeGroups = $this->repository
            ->getContentTypeService()
            ->loadContentType( $contentTypeId )
            ->getContentTypeGroups();

        foreach ( $contentTypeGroups as $group )
        {
            if ( $this->doMatch( $group->id ) )
            {
                return true;
            }
        }

        return false;
    }
}
