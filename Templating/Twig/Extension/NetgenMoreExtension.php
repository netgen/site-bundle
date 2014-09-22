<?php

namespace Netgen\Bundle\MoreBundle\Templating\Twig\Extension;

use Netgen\Bundle\MoreBundle\Helper\PathHelper;
use Netgen\Bundle\MoreBundle\Templating\GlobalHelper;
use Twig_Extension;
use Twig_SimpleFunction;

class NetgenMoreExtension extends Twig_Extension
{
    /**
     * @var \Netgen\Bundle\MoreBundle\Helper\PathHelper
     */
    protected $pathHelper;

    /**
     * @var \Netgen\Bundle\MoreBundle\Templating\GlobalHelper
     */
    protected $globalHelper;

    /**
     * Constructor
     *
     * @param \Netgen\Bundle\MoreBundle\Helper\PathHelper $pathHelper
     * @param \Netgen\Bundle\MoreBundle\Templating\GlobalHelper $globalHelper
     */
    public function __construct( PathHelper $pathHelper, GlobalHelper $globalHelper )
    {
        $this->pathHelper = $pathHelper;
        $this->globalHelper = $globalHelper;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'ngmore';
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction(
                'ngmore_get_path',
                array( $this, 'getPath' ),
                array( 'is_safe' => array( 'html' ) )
            )
        );
    }

    /**
     * Returns the path for specified location ID
     *
     * @param mixed $locationId
     *
     * @return array
     */
    public function getPath( $locationId )
    {
        return $this->pathHelper->getPath( $locationId );
    }

    /**
     * Returns a list of global variables to add to the existing list.
     *
     * @return array An array of global variables
     */
    public function getGlobals()
    {
        return array( 'ngmore' => $this->globalHelper );
    }
}
