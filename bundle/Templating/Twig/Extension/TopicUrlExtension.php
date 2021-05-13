<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Templating\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TopicUrlExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'ngsite_topic_path',
                [TopicUrlRuntime::class, 'getTopicPath'],
            ),
            new TwigFunction(
                'ngsite_topic_url',
                [TopicUrlRuntime::class, 'getTopicUrl'],
            ),
        ];
    }
}
