<?php

namespace Netgen\Bundle\MoreBundle\Pagerfanta\View\Template;

use Pagerfanta\View\Template\TwitterBootstrap3Template as BaseTwitterBootstrap3Template;

class TwitterBootstrap3Template extends BaseTwitterBootstrap3Template
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setOptions(
            array(
                'prev_message' => '&laquo;',
                'next_message' => '&raquo;'
            )
        );
    }

    /**
     * Renders the container for the pagination.
     *
     * The %pages% placeholder will be replaced by the rendering of pages
     *
     * @return string
     */
    public function container()
    {
        return '<div class="text-center">' . parent::container() . '</div>';
    }

    /**
     * Returns HTML code for previous link when it is disabled
     *
     * @return string
     */
    public function previousDisabled()
    {
        return '';
    }

    /**
     * Returns HTML code for next link when it is disabled
     *
     * @return string
     */
    public function nextDisabled()
    {
        return '';
    }
}
