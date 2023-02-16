<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Core\MVC\Symfony\Matcher\ContentBased\Id;

use Ibexa\Core\MVC\Symfony\Matcher\ViewMatcherInterface;
use Ibexa\Core\MVC\Symfony\View\View;
use Netgen\Bundle\IbexaSiteApiBundle\View\ContentValueView;
use Netgen\Bundle\SiteBundle\Core\MVC\Symfony\Matcher\ConfigResolverBased;

final class Content extends ConfigResolverBased implements ViewMatcherInterface
{
    public function match(View $view): bool
    {
        if (!$view instanceof ContentValueView) {
            return false;
        }

        return $this->doMatch($view->getSiteContent()->contentInfo->id);
    }
}
