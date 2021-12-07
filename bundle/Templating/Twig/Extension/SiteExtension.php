<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Templating\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SiteExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'ngsite_location_path',
                [SiteRuntime::class, 'getLocationPath'],
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'ngsite_language_name',
                [SiteRuntime::class, 'getLanguageName'],
            ),
            new TwigFunction(
                'ngsite_content_name',
                [SiteRuntime::class, 'getContentName'],
            ),
            new TwigFunction(
                'ngsite_location_name',
                [SiteRuntime::class, 'getLocationName'],
            ),
            new TwigFunction(
                'ngsite_image_url',
                [SiteRuntime::class, 'getImageUrl'],
            ),
            new TwigFunction(
                'ngsite_reading_time',
                [SiteRuntime::class, 'calculateReadingTime'],
            ),
        ];
    }
}
