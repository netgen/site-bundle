<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Layouts\Query;

use Ibexa\Contracts\Core\Persistence\Content\ObjectState\Handler as ObjectStateHandler;
use Ibexa\Contracts\Core\Persistence\Content\Section\Handler as SectionHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Netgen\IbexaSiteApi\API\FindService;
use Netgen\IbexaSiteApi\API\LoadService;
use Netgen\Layouts\API\Values\Collection\Query;
use Netgen\Layouts\Collection\QueryType\QueryTypeHandlerInterface;
use Netgen\Layouts\Ibexa\Collection\QueryType\Handler\Traits;
use Netgen\Layouts\Ibexa\ContentProvider\ContentProviderInterface;
use Netgen\Layouts\Ibexa\Parameters\ParameterType as IbexaParameterType;
use Netgen\Layouts\Parameters\ParameterBuilderInterface;
use Netgen\Layouts\Parameters\ParameterType;
use Netgen\TagsBundle\API\Repository\Values\Content\Query\Criterion\TagId;
use Netgen\TagsBundle\API\Repository\Values\Tags\Tag;
use Netgen\TagsBundle\Core\FieldType\Tags\Value as TagsFieldValue;

use function array_filter;
use function array_map;
use function count;

final class ContentByTopicHandler implements QueryTypeHandlerInterface
{
    use Traits\ContentTypeFilterTrait;
    use Traits\MainLocationFilterTrait;
    use Traits\ObjectStateFilterTrait;
    use Traits\ParentLocationTrait;
    use Traits\QueryTypeFilterTrait;
    use Traits\SectionFilterTrait;
    use Traits\SortTrait;

    public function __construct(
        private LoadService $loadService,
        private FindService $findService,
        LocationService $locationService,
        SectionHandler $sectionHandler,
        ObjectStateHandler $objectStateHandler,
        ContentProviderInterface $contentProvider,
    ) {
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
            ],
        );

        $builder->get('use_topic_from_current_content')->add(
            'topic_content_id',
            IbexaParameterType\ContentType::class,
            [
                'allow_invalid' => true,
            ],
        );

        $this->buildParentLocationParameters($builder);
        $this->buildSortParameters($builder);
        $this->buildQueryTypeParameters($builder, $advancedGroup);
        $this->buildMainLocationParameters($builder, $advancedGroup);
        $this->buildContentTypeFilterParameters($builder, $advancedGroup);
        $this->buildSectionFilterParameters($builder, $advancedGroup);
        $this->buildObjectStateFilterParameters($builder, $advancedGroup);
    }

    public function getValues(Query $query, int $offset = 0, ?int $limit = null): iterable
    {
        $parentLocation = $this->getParentLocation($query);

        if (!$parentLocation instanceof Location) {
            return [];
        }

        [$topicContent, $topicTag] = $this->getTopicTag($query);

        if (!$topicContent instanceof Content || !$topicTag instanceof Tag) {
            return [];
        }

        $locationQuery = $this->buildLocationQuery($query, $parentLocation, $topicContent, $topicTag);
        $locationQuery->offset = $offset;
        $locationQuery->limit = $limit ?? 100;

        // We're disabling query count for performance reasons, however
        // it can only be disabled if limit is not 0
        $locationQuery->performCount = $locationQuery->limit === 0;

        $searchResult = $this->findService->findLocations($locationQuery);

        return array_map(
            static fn (SearchHit $searchHit) => $searchHit->valueObject,
            $searchResult->searchHits,
        );
    }

    public function getCount(Query $query): int
    {
        $parentLocation = $this->getParentLocation($query);

        if (!$parentLocation instanceof Location) {
            return 0;
        }

        [$topicContent, $topicTag] = $this->getTopicTag($query);

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
            $query->getParameter('use_topic_from_current_content')->getValue() === true
            || $query->getParameter('use_current_location')->getValue() === true;
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
            static fn ($criterion): bool => $criterion instanceof Criterion,
        );

        $locationQuery->filter = new Criterion\LogicalAnd($criteria);
        $locationQuery->sortClauses = $this->getSortClauses($query, $parentLocation);

        return $locationQuery;
    }

    /**
     * @return mixed[]|null[]
     */
    private function getTopicTag(Query $query): array
    {
        $content = null;
        $contentId = $query->getParameter('topic_content_id')->getValue();

        if ((bool) $query->getParameter('use_topic_from_current_content')->getValue()) {
            $content = $this->contentProvider->provideContent();
        } elseif ($contentId !== null) {
            try {
                $content = $this->loadService->loadContent($contentId)->innerContent;
            } catch (NotFoundException|UnauthorizedException) {
                // Do nothing
            }
        }

        if (!$content instanceof Content || !isset($content->fields['topic'])) {
            return [null, null];
        }

        $fieldValue = $content->getFieldValue('topic');
        if (!$fieldValue instanceof TagsFieldValue || count($fieldValue->tags) === 0) {
            return [null, null];
        }

        return [$content, $fieldValue->tags[0]];
    }
}
