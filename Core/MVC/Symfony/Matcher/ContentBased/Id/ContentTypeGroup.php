<?php

namespace Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher\ContentBased\Id;

use eZ\Publish\Core\MVC\Symfony\Matcher\ViewMatcherInterface;
use Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher\ConfigResolverBased;
use eZ\Publish\Core\MVC\Symfony\View\ContentView;
use eZ\Publish\Core\MVC\Symfony\View\View;

class ContentTypeGroup extends ConfigResolverBased implements ViewMatcherInterface
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

        $contentTypeGroups = $this->repository
            ->getContentTypeService()
            ->loadContentType( $view->getContent()->contentInfo->contentTypeId )
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
