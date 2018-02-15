<?php

namespace Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher\ContentBased\Id;

use Netgen\EzPlatformSiteApi\API\Values\Location as APILocation;
use eZ\Publish\Core\MVC\Symfony\Matcher\ViewMatcherInterface;
use Netgen\Bundle\EzPlatformSiteApiBundle\View\LocationValueView;
use eZ\Publish\Core\MVC\Symfony\View\View;
use Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher\ConfigResolverBased;

class ParentLocation extends ConfigResolverBased implements ViewMatcherInterface
{
    /**
     * Checks if View object matches.
     *
     * @param \eZ\Publish\Core\MVC\Symfony\View\View $view
     *
     * @return bool
     */
    public function match(View $view)
    {
        if (!$view instanceof LocationValueView) {
            return false;
        }

        $location = $view->getSiteLocation();
        if (!$location instanceof APILocation) {
            return false;
        }

        return $this->doMatch($location->parentLocationId);
    }
}
