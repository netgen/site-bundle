<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher\ContentBased\Id;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\Core\MVC\Symfony\Matcher\ViewMatcherInterface;
use eZ\Publish\Core\MVC\Symfony\View\View;
use Netgen\Bundle\EzPlatformSiteApiBundle\View\LocationValueView;
use Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher\ConfigResolverBased;
use Netgen\EzPlatformSiteApi\API\Values\Location as APILocation;

class ParentContentType extends ConfigResolverBased implements ViewMatcherInterface
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

        try {
            $parent = $location->parent;
        } catch (NotFoundException $e) {
            return false;
        }

        return $this->doMatch($parent->contentInfo->contentTypeId);
    }
}
