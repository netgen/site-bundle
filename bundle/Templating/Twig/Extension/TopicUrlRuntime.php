<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Templating\Twig\Extension;

use Netgen\Bundle\SiteBundle\Topic\UrlGenerator;
use Netgen\TagsBundle\API\Repository\Values\Tags\Tag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class TopicUrlRuntime
{
    public function __construct(private UrlGenerator $topicUrlGenerator)
    {
    }

    /**
     * Returns the path for the topic specified by provided tag.
     *
     * @param array<string, mixed> $parameters
     */
    public function getTopicPath(Tag $tag, array $parameters = [], bool $relative = false): string
    {
        return $this->topicUrlGenerator->generate(
            $tag,
            $parameters,
            $relative ?
                UrlGeneratorInterface::RELATIVE_PATH :
                UrlGeneratorInterface::ABSOLUTE_PATH,
        );
    }

    /**
     * Returns the URL for the topic specified by provided tag.
     *
     * @param array<string, mixed> $parameters
     */
    public function getTopicUrl(Tag $tag, array $parameters = [], bool $schemeRelative = false): string
    {
        return $this->topicUrlGenerator->generate(
            $tag,
            $parameters,
            $schemeRelative ?
                UrlGeneratorInterface::NETWORK_PATH :
                UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }
}
