<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Layouts\Query;

use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Search\SearchHit;
use eZ\Publish\SPI\Persistence\Content\ObjectState\Handler as ObjectStateHandler;
use eZ\Publish\SPI\Persistence\Content\Section\Handler as SectionHandler;
use eZ\Publish\SPI\Persistence\Content\Type\Handler as ContentTypeHandler;
use Netgen\BlockManager\API\Values\Collection\Query;
use Netgen\BlockManager\Collection\QueryType\QueryTypeHandlerInterface;
use Netgen\BlockManager\Ez\Collection\QueryType\Handler\Traits;
use Netgen\BlockManager\Ez\ContentProvider\ContentProviderInterface;
use Netgen\BlockManager\Ez\Parameters\ParameterType as EzParameterType;
use Netgen\BlockManager\Parameters\ParameterBuilderInterface;
use Netgen\BlockManager\Parameters\ParameterType;
use Netgen\EzPlatformSiteApi\API\FindService;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Netgen\TagsBundle\API\Repository\Values\Content\Query\Criterion\TagId;
use Netgen\TagsBundle\API\Repository\Values\Tags\Tag;
use Netgen\TagsBundle\Core\FieldType\Tags\Value as TagsFieldValue;

class ContentByTopicHandler implements QueryTypeHandlerInterface
{
    use Traits\ParentLocationTrait;
    use Traits\SortTrait;
    use Traits\QueryTypeFilterTrait;
    use Traits\MainLocationFilterTrait;
    use Traits\ContentTypeFilterTrait;
    use Traits\SectionFilterTrait;
    use Traits\ObjectStateFilterTrait;

    /**
     * @var \Netgen\EzPlatformSiteApi\API\LoadService
     */
    private $loadService;

    /**
     * @var \Netgen\EzPlatformSiteApi\API\FindService
     */
    private $findService;

    public function __construct(
        LocationService $locationService,
        LoadService $loadService,
        FindService $findService,
        ContentTypeHandler $contentTypeHandler,
        SectionHandler $sectionHandler,
        ObjectStateHandler $objectStateHandler,
        ContentProviderInterface $contentProvider
    ) {
        $this->loadService = $loadService;
        $this->findService = $findService;

        $this->setContentTypeHandler($contentTypeHandler);
        $this->setSectionHandler($sectionHandler);
        $this->setObjectStateHandler($objectStateHandler);
        $this->setContentProvider($contentProvider);
        $this->setLocationService($locationService);
    }

    public function buildParameters(ParameterBuilderInterface $builder): void
    {
        $advancedGroup = [self::GROUP_ADVANCED];

        $builder->add(
            'use_topic_from_current_content',
            ParameterType\Compound\BooleanType::class,
            [
                'reverse' => true,
            ]
        );

        $builder->get('use_topic_from_current_content')->add(
            'topic_content_id',
            EzParameterType\ContentType::class,
            [
                'allow_invalid' => true,
            ]
        );

        $this->buildParentLocationParameters($builder);
        $this->buildSortParameters($builder);
        $this->buildQueryTypeParameters($builder, $advancedGroup);
        $this->buildMainLocationParameters($builder, $advancedGroup);
        $this->buildContentTypeFilterParameters($builder, $advancedGroup);
        $this->buildSectionFilterParameters($builder, $advancedGroup);
        $this->buildObjectStateFilterParameters($builder, $advancedGroup);
    }

    public function getValues(Query $query, $offset = 0, $limit = null)
    {
        $parentLocation = $this->getParentLocation($query);

        if (!$parentLocation instanceof Location) {
            return [];
        }

        list($topicContent, $topicTag) = $this->getTopicTag($query);

        if (!$topicContent instanceof Content || !$topicTag instanceof Tag) {
            return [];
        }

        $locationQuery = $this->buildLocationQuery($query, $parentLocation, $topicContent, $topicTag);
        $locationQuery->offset = $offset;
        $locationQuery->limit = $limit;

        // We're disabling query count for performance reasons, however
        // it can only be disabled if limit is not 0
        $locationQuery->performCount = $locationQuery->limit === 0;

        $searchResult = $this->findService->findLocations($locationQuery);

        return array_map(
            function (SearchHit $searchHit) {
                return $searchHit->valueObject;
            },
            $searchResult->searchHits
        );
    }

    public function getCount(Query $query): int
    {
        $parentLocation = $this->getParentLocation($query);

        if (!$parentLocation instanceof Location) {
            return 0;
        }

        list($topicContent, $topicTag) = $this->getTopicTag($query);

        if (!$topicContent instanceof Content || !$topicTag instanceof Tag) {
            return 0;
        }

        $locationQuery = $this->buildLocationQuery($query, $parentLocation, $topicContent, $topicTag);
        $locationQuery->limit = 0;

        $searchResult = $this->findService->findLocations($locationQuery);

        return $searchResult->totalCount ?? 0;
    }

    public function isContextual(Query $query): bool
    {
        return
            $query->getParameter('use_topic_from_current_content')->getValue() === true ||
            $query->getParameter('use_current_location')->getValue() === true;
    }

    /**
     * Builds the query from current parameters.
     */
    private function buildLocationQuery(Query $query, Location $parentLocation, Content $topicContent, Tag $topicTag): LocationQuery
    {
        $locationQuery = new LocationQuery();

        $criteria = [
            new Criterion\Subtree($parentLocation->pathString),
            new Criterion\Visibility(Criterion\Visibility::VISIBLE),
            new Criterion\LogicalNot(new Criterion\LocationId($parentLocation->id)),
            new Criterion\LogicalNot(new Criterion\ContentId($topicContent->id)),
            $this->getMainLocationFilterCriteria($query),
            $this->getQueryTypeFilterCriteria($query, $parentLocation),
            $this->getContentTypeFilterCriteria($query),
            $this->getSectionFilterCriteria($query),
            $this->getObjectStateFilterCriteria($query),
            new TagId($topicTag->id),
        ];

        $criteria = array_filter(
            $criteria,
            function ($criterion): bool {
                return $criterion instanceof Criterion;
            }
        );

        $locationQuery->filter = new Criterion\LogicalAnd($criteria);
        $locationQuery->sortClauses = $this->getSortClauses($query, $parentLocation);

        return $locationQuery;
    }

    private function getTopicTag(Query $query): array
    {
        $content = null;
        $contentId = $query->getParameter('topic_content_id')->getValue();

        if ($query->getParameter('use_topic_from_current_content')->getValue()) {
            $content = $this->contentProvider->provideContent();
        } elseif (!empty($contentId)) {
            $content = $this->loadService->loadContent($contentId)->innerContent;
        }

        if (!$content instanceof Content || !isset($content->fields['topic'])) {
            return [null, null];
        }

        $fieldValue = $content->getField('topic')->value;
        if (!$fieldValue instanceof TagsFieldValue || empty($fieldValue->tags)) {
            return [null, null];
        }

        return [$content, $fieldValue->tags[0]];
    }
}
