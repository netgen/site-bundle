<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Templating\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NetgenMoreExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'ngmore_location_path',
                [NetgenMoreRuntime::class, 'getLocationPath'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'ngmore_language_name',
                [NetgenMoreRuntime::class, 'getLanguageName']
            ),
        ];
    }
}
