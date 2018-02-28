<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Templating\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NetgenMoreExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return array(
            new TwigFunction(
                'ngmore_location_path',
                array(NetgenMoreRuntime::class, 'getLocationPath'),
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'ngmore_language_name',
                array(NetgenMoreRuntime::class, 'getLanguageName')
            ),
        );
    }
}
