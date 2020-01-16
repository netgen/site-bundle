<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Menu\Factory\LocationFactory;

use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Search\SearchHit;
use eZ\Publish\Core\FieldType\Url\Value as UrlValue;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Symfony\SiteAccess\URILexer;
use Knp\Menu\ItemInterface;
use Netgen\EzPlatformSiteApi\API\FilterService;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

class MenuItemExtension implements ExtensionInterface
{
    /**
     * @var \Netgen\EzPlatformSiteApi\API\LoadService
     */
    protected $loadService;

    /**
     * @var \Netgen\EzPlatformSiteApi\API\FilterService
     */
    protected $filterService;

    /**
     * @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    protected $requestStack;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var \Psr\Log\NullLogger
     */
    protected $logger;

    public function __construct(
        LoadService $loadService,
        FilterService $filterService,
        UrlGeneratorInterface $urlGenerator,
        RequestStack $requestStack,
        ConfigResolverInterface $configResolver,
        LoggerInterface $logger = null
    ) {
        $this->loadService = $loadService;
        $this->filterService = $filterService;
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
        $this->configResolver = $configResolver;
        $this->logger = $logger ?: new NullLogger();
    }

    public function matches(Location $location): bool
    {
        return $location->contentInfo->contentTypeIdentifier === 'ng_menu_item';
    }

    public function buildItem(ItemInterface $item, Location $location): void
    {
        $this->buildItemFromContent($item, $location->content);

        if ($location->content->getField('target_blank')->value->bool) {
            $item->setLinkAttribute('target', '_blank')
                ->setLinkAttribute('rel', 'nofollow noopener noreferrer');
        }

        $this->buildChildItems($item, $location->content);
    }

    protected function buildItemFromContent(ItemInterface $item, Content $content): void
    {
        if (!$content->getField('item_url')->isEmpty()) {
            $this->buildItemFromUrl($item, $content->getField('item_url')->value, $content);

            return;
        }

        $relatedContent = null;
        if (!$content->getField('item_object')->isEmpty()) {
            $relatedContent = $content->getFieldRelation('item_object');
        }

        if (!$relatedContent instanceof Content) {
            return;
        }

        if ($relatedContent->mainLocation->invisible) {
            $this->logger->error(sprintf('Menu item (#%s) has a related object (#%s) that is not visible.', $content->id, $relatedContent->id));

            return;
        }

        $this->buildItemFromRelatedContent($item, $content, $relatedContent);
    }

    protected function buildItemFromUrl(ItemInterface $item, UrlValue $urlValue, Content $content): void
    {
        $uri = $urlValue->link;

        if (mb_stripos($urlValue->link, 'http') !== 0) {
            $currentSiteAccess = $this->requestStack->getMasterRequest()->attributes->get('siteaccess');
            if ($currentSiteAccess->matcher instanceof URILexer) {
                $uri = $currentSiteAccess->matcher->analyseLink($uri);
            }
        }

        $item->setUri($uri);

        if (!empty($urlValue->text)) {
            $item->setLinkAttribute('title', $urlValue->text);

            if (!$content->getField('use_menu_item_name')->value->bool) {
                $item->setLabel($urlValue->text);
            }
        }
    }

    protected function buildItemFromRelatedContent(ItemInterface $item, Content $content, Content $relatedContent): void
    {
        $item
            ->setUri($this->urlGenerator->generate($relatedContent))
            ->setExtra('ezlocation', $relatedContent->mainLocation)
            ->setAttribute('id', 'menu-item-location-id-' . $relatedContent->mainLocationId)
            ->setLinkAttribute('title', $item->getLabel());

        if (!$content->getField('use_menu_item_name')->value->bool) {
            $item->setLabel($relatedContent->name);
        }

        $containerClasses = $this->configResolver->getParameter('container_content_types', 'ngsite');
        if (in_array($relatedContent->contentInfo->contentTypeIdentifier, $containerClasses, true)) {
            // Disable link for content types that act as simple content containers
            // and that have no their own full views
            $item->setUri('');
        }
    }

    protected function buildChildItems(ItemInterface $item, Content $content): void
    {
        $childLocations = [];

        if (!$content->getField('parent_node')->isEmpty()) {
            $destinationContent = $content->getFieldRelation('parent_node');
            if (!$destinationContent instanceof Content) {
                return;
            }

            if ($destinationContent->mainLocation->invisible) {
                $this->logger->error(sprintf('Menu item (#%s) has a related object (#%s) that is not visible.', $content->id, $destinationContent->id));

                return;
            }

            $criteria = [
                new Criterion\Visibility(Criterion\Visibility::VISIBLE),
                new Criterion\ParentLocationId($destinationContent->mainLocation->id),
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
                        new Criterion\ContentTypeIdentifier($contentTypeFilter->identifiers)
                    );
                }
            }

            $query = new LocationQuery();
            $query->filter = new Criterion\LogicalAnd($criteria);
            $query->sortClauses = $destinationContent->mainLocation->innerLocation->getSortClauses();

            if (!$content->getField('limit')->isEmpty()) {
                /** @var \eZ\Publish\Core\FieldType\Integer\Value $limit */
                $limit = $content->getField('limit')->value;
                if ($limit->value > 0) {
                    $query->limit = $limit->value;
                }
            }

            $searchResult = $this->filterService->filterLocations($query);

            $childLocations = array_map(
                static function (SearchHit $searchHit) {
                    return $searchHit->valueObject;
                },
                $searchResult->searchHits
            );
        } elseif (!$content->getField('menu_items')->isEmpty()) {
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
        }

        foreach ($childLocations as $location) {
            $item->addChild(null, ['ezlocation' => $location]);
        }
    }
}
