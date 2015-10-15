<?php

namespace Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher\Block\Id;

use eZ\Publish\Core\MVC\Symfony\Matcher\ViewMatcherInterface;
use Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher\ConfigResolverBased;
use eZ\Publish\Core\MVC\Symfony\View\BlockValueView;
use eZ\Publish\Core\MVC\Symfony\View\View;

class Block extends ConfigResolverBased implements ViewMatcherInterface
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
        if ( !$view instanceof BlockValueView )
        {
            return false;
        }

        return $this->doMatch( $view->getBlock()->id );
    }
}
