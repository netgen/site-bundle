<?php

namespace Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher\ContentBased\Id;

use eZ\Publish\Core\MVC\Symfony\Matcher\ViewMatcherInterface;
use Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentValueView;
use eZ\Publish\Core\MVC\Symfony\View\View;
use Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher\ConfigResolverBased;

class Section extends ConfigResolverBased implements ViewMatcherInterface
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
        if (!$view instanceof ContentValueView) {
            return false;
        }

        return $this->doMatch($view->getSiteContent()->contentInfo->sectionId);
    }
}
