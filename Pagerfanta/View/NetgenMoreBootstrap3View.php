<?php

namespace Netgen\Bundle\MoreBundle\Pagerfanta\View;

use Netgen\Bundle\MoreBundle\Pagerfanta\View\Template\TwitterBootstrap3Template;
use Pagerfanta\View\TwitterBootstrap3View;

class NetgenMoreBootstrap3View extends TwitterBootstrap3View
{
    /**
     * Returns the default template to render with.
     *
     * @return \Pagerfanta\View\Template\TemplateInterface
     */
    protected function createDefaultTemplate()
    {
        return new TwitterBootstrap3Template();
    }

    /**
     * Sets the default proximity.
     *
     * @return int
     */
    protected function getDefaultProximity()
    {
        return 2;
    }

    /**
     * Returns the canonical name.
     *
     * @return string The canonical name
     */
    public function getName()
    {
        return 'ngmore_bootstrap3';
    }
}
