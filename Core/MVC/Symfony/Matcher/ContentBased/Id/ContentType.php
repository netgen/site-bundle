<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher\ContentBased\Id;

use eZ\Publish\Core\MVC\Symfony\Matcher\ViewMatcherInterface;
use eZ\Publish\Core\MVC\Symfony\View\View;
use Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentValueView;
use Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher\ConfigResolverBased;

class ContentType extends ConfigResolverBased implements ViewMatcherInterface
{
    public function match(View $view)
    {
        if (!$view instanceof ContentValueView) {
            return false;
        }

        return $this->doMatch($view->getSiteContent()->contentInfo->contentTypeId);
    }
}
