<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Core\MVC\Symfony\Matcher\ContentBased\Id;

use Ibexa\Core\MVC\Symfony\Matcher\ViewMatcherInterface;
use Ibexa\Core\MVC\Symfony\View\View;
use Netgen\Bundle\EzPlatformSiteApiBundle\View\LocationValueView;
use Netgen\Bundle\SiteBundle\Core\MVC\Symfony\Matcher\ConfigResolverBased;
use Netgen\EzPlatformSiteApi\API\Values\Location as APILocation;

class ParentLocation extends ConfigResolverBased implements ViewMatcherInterface
{
    public function match(View $view): bool
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
