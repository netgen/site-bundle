<?php

namespace Netgen\Bundle\MoreBundle\Templating\Twig\Extension;

use Twig_Extension;
use Twig_SimpleFunction;

class NetgenMoreExtension extends Twig_Extension
{
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction(
                'ngmore_location_path',
                array(NetgenMoreRuntime::class, 'getLocationPath'),
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'ngmore_language_name',
                array(NetgenMoreRuntime::class, 'getLanguageName')
            ),
        );
    }
}
