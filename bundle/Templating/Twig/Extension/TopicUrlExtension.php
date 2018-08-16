<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Templating\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TopicUrlExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'ngmore_topic_path',
                [TopicUrlRuntime::class, 'getTopicPath']
            ),
            new TwigFunction(
                'ngmore_topic_url',
                [TopicUrlRuntime::class, 'getTopicUrl']
            ),
        ];
    }
}
