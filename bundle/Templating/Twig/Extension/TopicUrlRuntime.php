<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Templating\Twig\Extension;

use Netgen\Bundle\MoreBundle\Topic\UrlGenerator;
use Netgen\TagsBundle\API\Repository\Values\Tags\Tag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TopicUrlRuntime
{
    /**
     * @var \Netgen\Bundle\MoreBundle\Topic\UrlGenerator
     */
    private $topicUrlGenerator;

    public function __construct(UrlGenerator $topicUrlGenerator)
    {
        $this->topicUrlGenerator = $topicUrlGenerator;
    }

    /**
     * Returns the path for the topic specified by provided tag.
     */
    public function getTopicPath(Tag $tag, array $parameters = [], bool $relative = false): string
    {
        return $this->topicUrlGenerator->generate(
            $tag,
            $parameters,
            $relative ?
                UrlGeneratorInterface::RELATIVE_PATH :
                UrlGeneratorInterface::ABSOLUTE_PATH
        );
    }

    /**
     * Returns the URL for the topic specified by provided tag.
     */
    public function getTopicUrl(Tag $tag, array $parameters = [], bool $schemeRelative = false): string
    {
        return $this->topicUrlGenerator->generate(
            $tag,
            $parameters,
            $schemeRelative ?
                UrlGeneratorInterface::NETWORK_PATH :
                UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
