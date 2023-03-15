<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Menu\Factory\LocationFactory;

use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Knp\Menu\ItemInterface;
use Netgen\IbexaSiteApi\API\FilterService;
use Netgen\IbexaSiteApi\API\LoadService;
use Netgen\IbexaSiteApi\API\Values\Content;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

use function array_map;
use function sprintf;

final class ChildrenBuilder
{
    public function __construct(
        private LoadService $loadService,
        private FilterService $filterService,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function buildChildItems(ItemInterface $item, Content $content): void
    {
        if (!$content->getField('parent_node')->isEmpty()) {
            $this->buildChildrenFromRelatedParentNode($item, $content);
        } elseif (!$content->getField('menu_items')->isEmpty()) {
            $this->buildChildrenFromRelatedMenuItems($item, $content);
        }
    }

    private function buildChildrenFromRelatedParentNode(ItemInterface $item, Content $content, ?Content $parentContent = null, int $currentDepth = 1): void
    {
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
            /** @var \Ibexa\Core\FieldType\Integer\Value $limit */
            $limit = $content->getField('limit')->value;
            if ($limit->value > 0) {
                $query->limit = $limit->value;
            }
        }

        $searchResult = $this->filterService->filterLocations($query);

        /** @var \Netgen\IbexaSiteApi\API\Values\Location[] $childLocations */
        $childLocations = array_map(
            static fn (SearchHit $searchHit) => $searchHit->valueObject,
            $searchResult->searchHits,
        );

        $maxDepth = 1;
        if (!$content->getField('depth')->isEmpty()) {
            $maxDepth = $content->getFieldValue('depth')->value;
        }

        foreach ($childLocations as $index => $location) {
            $childItem = $item->addChild('', ['ibexa_location' => $location, 'index' => $index, 'menu_name' => $item->getExtra('menu_name')]);
            if ($currentDepth <= $maxDepth) {
                $this->buildChildrenFromRelatedParentNode($childItem, $content, $location->content, $currentDepth + 1);
            }
        }
    }

    private function buildChildrenFromRelatedMenuItems(ItemInterface $item, Content $content): void
    {
        $childLocations = [];

        foreach ($content->getField('menu_items')->value->destinationContentIds as $contentId) {
            if ((int) $contentId <= 0) {
                $this->logger->error(sprintf('Empty content ID in RelationList field "%s" for content #%s', 'menu_items', $content->id));

                continue;
            }

            try {
                $childLocations[] = $this->loadService->loadContent($contentId)->mainLocation;
            } catch (Throwable $t) {
                $this->logger->error($t->getMessage());

                continue;
            }
        }

        foreach ($childLocations as $index => $location) {
            $item->addChild('', ['ibexa_location' => $location, 'index' => $index, 'menu_name' => $item->getExtra('menu_name')]);
        }
    }
}
