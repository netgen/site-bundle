<?php

namespace Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher\Block\Id;

use eZ\Publish\Core\FieldType\Page\Parts\Block as PageBlock;
use eZ\Publish\Core\MVC\Symfony\Matcher\Block\MatcherInterface;
use Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher\ConfigResolverBased;
use eZ\Publish\Core\MVC\Symfony\View\BlockValueView;
use eZ\Publish\Core\MVC\Symfony\View\View;

class Zone extends ConfigResolverBased implements MatcherInterface
{
    /**
     * Checks if a Block object matches.
     *
     * @param \eZ\Publish\Core\FieldType\Page\Parts\Block $block
     *
     * @return bool
     */
    public function matchBlock( PageBlock $block )
    {
        return $this->doMatch( $block->zoneId );
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
        if ( !$view instanceof BlockValueView )
        {
            return false;
        }

        return $this->doMatch( $view->getBlock()->zoneId );
    }
}
