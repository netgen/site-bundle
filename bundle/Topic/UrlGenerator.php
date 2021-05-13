<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Topic;

use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Netgen\EzPlatformSiteApi\API\FindService;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Netgen\TagsBundle\API\Repository\Values\Content\Query\Criterion\TagId;
use Netgen\TagsBundle\API\Repository\Values\Tags\Tag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UrlGenerator
{
    private FindService $findService;

    private LoadService $loadService;

    private ConfigResolverInterface $configResolver;

    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        FindService $findService,
        LoadService $loadService,
        ConfigResolverInterface $configResolver,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->findService = $findService;
        $this->loadService = $loadService;
        $this->configResolver = $configResolver;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Returns the path for the topic specified by provided tag.
     */
    public function generate(Tag $tag, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->urlGenerator->generate($this->getTopicValueObject($tag), $parameters, $referenceType);
    }

    /**
     * If exists, returns the location of the content with ng_topic identifier connected to provided tag.
     *
     * Otherwise, the tag itself is returned.
     *
     * @return \Netgen\EzPlatformSiteApi\API\Values\Location|\Netgen\TagsBundle\API\Repository\Values\Tags\Tag
     */
    private function getTopicValueObject(Tag $tag)
    {
        $rootLocation = $this->loadService->loadLocation(
            $this->configResolver->getParameter('content.tree_root.location_id'),
        );

        $query = new LocationQuery();
        $query->limit = 1;

        $query->filter = new Criterion\LogicalAnd(
            [
                new Criterion\Subtree($rootLocation->pathString),
                new Criterion\LogicalNot(new Criterion\LocationId($rootLocation->id)),
                new Criterion\Location\IsMainLocation(Criterion\Location\IsMainLocation::MAIN),
                new Criterion\Visibility(Criterion\Visibility::VISIBLE),
                new Criterion\ContentTypeIdentifier(['ng_topic']),
                new TagId($tag->id),
            ],
        );

        $searchResult = $this->findService->findLocations($query);

        if (!empty($searchResult->searchHits)) {
            return $searchResult->searchHits[0]->valueObject;
        }

        return $tag;
    }
}
