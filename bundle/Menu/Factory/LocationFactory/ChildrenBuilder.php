<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Menu\Factory\LocationFactory;

use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Search\SearchHit;
use Knp\Menu\ItemInterface;
use Netgen\EzPlatformSiteApi\API\FilterService;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

use function array_map;
use function sprintf;

class ChildrenBuilder
{
    protected LoadService $loadService;

    protected FilterService $filterService;

    protected LoggerInterface $logger;

    public function __construct(
        LoadService $loadService,
        FilterService $filterService,
        ?LoggerInterface $logger = null
    ) {
        $this->loadService = $loadService;
        $this->filterService = $filterService;
        $this->logger = $logger ?? new NullLogger();
    }

    public function buildChildItems(ItemInterface $item, Content $content): void
    {
        if (!$content->getField('parent_node')->isEmpty()) {
            $childLocations = $this->buildChildrenFromRelatedParentNode($item, $content);
        } elseif (!$content->getField('menu_items')->isEmpty()) {
            $this->buildChildrenFromRelatedMenuItems($item, $content);
        }
    }

    protected function buildChildrenFromRelatedParentNode(ItemInterface $item, Content $content, ?Content $parentContent = null, int $currentDepth = 1): void
    {
        $childLocations = [];

        $parentContent = $parentContent instanceof Content ? $parentContent : $content->getFieldRelation('parent_node');

        if (!$parentContent instanceof Content) {
            return;
        }

        if ($parentContent->mainLocation->invisible) {
            $this->logger->error(sprintf('Menu item (#%s) has a related object (#%s) that is not visible.', $content->id, $parentContent->id));

            return;
        }

        $criteria = [
            new Criterion\Visibility(Criterion\Visibility::VISIBLE),
            new Criterion\ParentLocationId($parentContent->mainLocation->id),
        ];

        if (!$content->getField('class_filter')->isEmpty() && !$content->getField('class_filter_type')->isEmpty()) {
            /** @var \Netgen\Bundle\ContentTypeListBundle\Core\FieldType\ContentTypeList\Value $contentTypeFilter */
            $contentTypeFilter = $content->getField('class_filter')->value;

            /** @var \Netgen\Bundle\EnhancedSelectionBundle\Core\FieldType\EnhancedSelection\Value $filterType */
            $filterType = $content->getField('class_filter_type')->value;

            if ($filterType->identifiers[0] === 'include') {
                $criteria[] = new Criterion\ContentTypeIdentifier($contentTypeFilter->identifiers);
            } elseif ($filterType->identifiers[0] === 'exclude') {
                $criteria[] = new Criterion\LogicalNot(
                    new Criterion\ContentTypeIdentifier($contentTypeFilter->identifiers),
                );
            }
        }

        $query = new LocationQuery();
        $query->filter = new Criterion\LogicalAnd($criteria);
        $query->sortClauses = $parentContent->mainLocation->innerLocation->getSortClauses();

        if (!$content->getField('limit')->isEmpty()) {
            /** @var \eZ\Publish\Core\FieldType\Integer\Value $limit */
            $limit = $content->getField('limit')->value;
            if ($limit->value > 0) {
                $query->limit = $limit->value;
            }
        }

        $searchResult = $this->filterService->filterLocations($query);

        $childLocations = array_map(
            static fn (SearchHit $searchHit) => $searchHit->valueObject,
            $searchResult->searchHits,
        );

        $maxDepth = 1;
        if (!$content->getField('depth')->isEmpty()) {
            $maxDepth = $content->getFieldValue('depth')->value;
        }

        foreach ($childLocations as $location) {
            $childItem = $item->addChild(null, ['ezlocation' => $location, 'menu_name' => $item->getExtra('menu_name')]);
            if ($currentDepth <= $maxDepth) {
                $this->buildChildrenFromRelatedParentNode($childItem, $content, $location->content, $currentDepth + 1);
            }
        }
    }

    protected function buildChildrenFromRelatedMenuItems(ItemInterface $item, Content $content): void
    {
        $childLocations = [];

        foreach ($content->getField('menu_items')->value->destinationLocationIds as $locationId) {
            if (empty($locationId)) {
                $this->logger->error(sprintf('Empty location ID in RelationList field "%s" for content #%s', 'menu_items', $content->id));

                continue;
            }

            try {
                $childLocations[] = $this->loadService->loadLocation($locationId);
            } catch (Throwable $t) {
                $this->logger->error($t->getMessage());

                continue;
            }
        }

        foreach ($childLocations as $location) {
            $item->addChild(null, ['ezlocation' => $location, 'menu_name' => $item->getExtra('menu_name')]);
        }
    }
}
