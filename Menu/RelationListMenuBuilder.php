<?php

namespace Netgen\Bundle\MoreBundle\Menu;

use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Search\SearchHit;
use Netgen\EzPlatformSite\API\Values\Location;
use Netgen\EzPlatformSite\API\Values\Content;
use Knp\Menu\ItemInterface;
use Knp\Menu\FactoryInterface;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use Netgen\Bundle\MoreBundle\Helper\SortClauseHelper;
use Netgen\EzPlatformSite\API\FindService;
use Netgen\EzPlatformSite\API\LoadService;
use Psr\Log\NullLogger;
use Symfony\Component\Routing\RouterInterface;
use Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper;
use Netgen\Bundle\MoreBundle\Core\FieldType\RelationList\Value as RelationListValue;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;

class RelationListMenuBuilder
{
    /**
     * @var \Knp\Menu\FactoryInterface
     */
    protected $factory;

    /**
     * @var \Netgen\EzPlatformSite\API\LoadService
     */
    protected $loadService;

    /**
     * @var \Netgen\EzPlatformSite\API\FindService
     */
    protected $findService;

    /**
     * @var \Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper
     */
    protected $siteInfoHelper;

    /**
     * @var \Netgen\Bundle\MoreBundle\Helper\SortClauseHelper
     */
    protected $sortClauseHelper;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param \Knp\Menu\FactoryInterface $factory
     * @param \Netgen\EzPlatformSite\API\LoadService $loadService
     * @param \Netgen\EzPlatformSite\API\FindService $findService
     * @param \Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper $siteInfoHelper
     * @param \Netgen\Bundle\MoreBundle\Helper\SortClauseHelper $sortClauseHelper
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        FactoryInterface $factory,
        LoadService $loadService,
        FindService $findService,
        SiteInfoHelper $siteInfoHelper,
        SortClauseHelper $sortClauseHelper,
        RouterInterface $router,
        LoggerInterface $logger = null
    ) {
        $this->factory = $factory;
        $this->loadService = $loadService;
        $this->findService = $findService;
        $this->siteInfoHelper = $siteInfoHelper;
        $this->sortClauseHelper = $sortClauseHelper;
        $this->router = $router;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Creates the relation list menu.
     *
     * @param string $fieldDefIdentifier
     * @param mixed $contentId
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function createRelationListMenu($fieldDefIdentifier, $contentId = null)
    {
        $menu = $this->factory->createItem('root');

        if ($contentId !== null) {
            $content = $this->loadService->loadContent($contentId);
        } else {
            $content = $this->siteInfoHelper->getSiteInfoContent();
        }

        $menu->setAttribute('location-id', $content->mainLocationId);
        $this->generateFromRelationList($menu, $content, $fieldDefIdentifier);

        return $menu;
    }

    /**
     * Generates a menu item from a relation list in content.
     *
     * @param \Knp\Menu\ItemInterface $menuItem
     * @param \Netgen\EzPlatformSite\API\Values\Content $content
     * @param string $fieldDefIdentifier
     */
    protected function generateFromRelationList(ItemInterface $menuItem, Content $content, $fieldDefIdentifier)
    {
        if (!$content->hasField($fieldDefIdentifier)) {
            return;
        }

        $field = $content->getField($fieldDefIdentifier);
        if ($field->isEmpty()) {
            return;
        }

        if (!$field->value instanceof RelationListValue) {
            return;
        }

        foreach ($field->value->destinationLocationIds as $locationId) {
            try {
                if (empty($locationId)) {
                    $this->logger->error('[Relation Menu] Empty location id in relation list');

                    continue;
                }

                $location = $this->loadService->loadLocation($locationId);
            } catch (NotFoundException $e) {
                $this->logger->error($e->getMessage());

                continue;
            }

            if ($content->contentInfo->contentTypeIdentifier == 'ng_shortcut') {
                $this->generateFromNgShortcut($menuItem, $location);
            } elseif ($content->contentInfo->contentTypeIdentifier == 'ng_menu_item') {
                $this->generateFromNgMenuItem($menuItem, $location);
            } else {
                $this->addMenuItemsFromLocations($menuItem, array($location));
            }
        }
    }

    /**
     * Generates a menu item from Location object of ng_shortcut content type.
     *
     * @param \Knp\Menu\ItemInterface $menuItem
     * @param \Netgen\EzPlatformSite\API\Values\Location $location
     */
    protected function generateFromNgShortcut(ItemInterface $menuItem, Location $location)
    {
        $content = $this->loadService->loadContent($location->contentId);

        $uri = false;
        $menuItemId = $label = $content->name;
        $attributes = array();
        $linkAttributes = array();

        if (!$content->getField('url')->isEmpty()) {
            /** @var \eZ\Publish\Core\FieldType\Url\Value $fieldValue */
            $fieldValue = $content->getField('url')->value;
            if (stripos($fieldValue->link, 'http') === 0) {
                $menuItemId = $uri = $fieldValue->link;

                if (!empty($fieldValue->text)) {
                    $linkAttributes['title'] = $fieldValue->text;
                }
            } else {
                try {
                    $menuItemId = $uri = $this->router->generate(
                        'ez_legacy',
                        array(
                            'module_uri' => $fieldValue->link,
                        )
                    );
                } catch (InvalidArgumentException $e) {
                    $menuItemId = $uri = $fieldValue->link;
                }

                if (!empty($fieldValue->text)) {
                    $linkAttributes['title'] = $fieldValue->text;
                }
            }

            if ($content->getField('target_blank')->value->bool) {
                $linkAttributes['target'] = '_blank';
            }
        } elseif (!$content->getField('related_object')->isEmpty()) {
            /** @var \eZ\Publish\Core\FieldType\Relation\Value $fieldValue */
            $fieldValue = $content->getField('related_object')->value;

            try {
                $relatedContent = $this->loadService->loadContentInfo($fieldValue->destinationContentId);

                if ($relatedContent->published) {
                    $relatedContentName = $content->name;
                    $menuItemId = $relatedContent->mainLocationId;

                    $uri = $this->router->generate(
                        $relatedContent
                    ) . $content->getField('internal_url_suffix')->value->text;

                    if ($content->getField('use_shortcut_name')->value->bool) {
                        $label = $content->name;
                        $linkAttributes = array(
                            'title' => $label,
                        );
                    } else {
                        $label = $relatedContentName;
                        $linkAttributes = array(
                            'title' => $relatedContentName,
                        );
                    }

                    $attributes = array(
                        'id' => 'menu-item-location-id-' . $relatedContent->mainLocationId,
                    );

                    if ($content->getField('target_blank')->value->bool) {
                        $linkAttributes['target'] = '_blank';
                    }
                } else {
                    $this->logger->error('[Relation menu] Shortcut has related object that is not published.');
                }
            } catch (NotFoundException $e) {
                $this->logger->error($e->getMessage());
            }
        }

        $menuItem->addChild(
            $menuItemId,
            array(
                'uri' => $uri,
                'label' => $label,
                'attributes' => $attributes,
                'linkAttributes' => $linkAttributes,
            )
        );
    }

    /**
     * Generates a menu item and potential children from Location object of ng_menu_item content type.
     *
     * @param \Knp\Menu\ItemInterface $menuItem
     * @param \Netgen\EzPlatformSite\API\Values\Location $location
     */
    protected function generateFromNgMenuItem(ItemInterface $menuItem, Location $location)
    {
        $content = $this->loadService->loadContent($location->contentId);

        $uri = false;
        $menuItemId = $label = $content->name;
        $attributes = array();
        $linkAttributes = array();

        if (!$content->getField('item_url')->isEmpty()) {
            /** @var \eZ\Publish\Core\FieldType\Url\Value $fieldValue */
            $fieldValue = $content->getField('item_url')->value;
            if (stripos($fieldValue->link, 'http') === 0) {
                $menuItemId = $uri = $fieldValue->link;

                if (!empty($fieldValue->text)) {
                    $linkAttributes['title'] = $fieldValue->text;
                }
            } else {
                try {
                    $menuItemId = $uri = $this->router->generate(
                        'ez_legacy',
                        array(
                            'module_uri' => $fieldValue->link,
                        )
                    );
                } catch (InvalidArgumentException $e) {
                    $menuItemId = $uri = $fieldValue->link;
                }

                if (!empty($fieldValue->text)) {
                    $linkAttributes['title'] = $fieldValue->text;
                }
            }

            if ($content->getField('target_blank')->value->bool) {
                $linkAttributes['target'] = '_blank';
            }
        } elseif (!$content->getField('item_object')->isEmpty()) {
            /** @var \eZ\Publish\Core\FieldType\Relation\Value $fieldValue */
            $fieldValue = $content->getField('item_object')->value;

            try {
                $relatedContent = $this->loadService->loadContentInfo($fieldValue->destinationContentId);

                if ($relatedContent->published) {
                    $relatedContentName = $relatedContent->name;

                    $menuItemId = $relatedContent->mainLocationId;

                    $uri = $this->router->generate($relatedContent);

                    if ($content->getField('use_menu_item_name')->value->bool) {
                        $label = $content->name;
                        $linkAttributes = array(
                            'title' => $label,
                        );
                    } else {
                        $label = $relatedContentName;
                        $linkAttributes = array(
                            'title' => $relatedContentName,
                        );
                    }

                    $attributes = array(
                        'id' => 'menu-item-location-id-' . $relatedContent->mainLocationId,
                    );

                    if ($content->getField('target_blank')->value->bool) {
                        $linkAttributes['target'] = '_blank';
                    }
                } else {
                    $this->logger->error('[Relation menu] Menu item has related object that is not published.');
                }
            } catch (NotFoundException $e) {
                $this->logger->error($e->getMessage());
            }
        }

        $childItem = $menuItem->addChild(
            $menuItemId,
            array(
                'uri' => $uri,
                'label' => $label,
                'attributes' => $attributes,
                'linkAttributes' => $linkAttributes,
            )
        );

        if (!$content->getField('parent_node')->isEmpty()) {
            /** @var \eZ\Publish\Core\FieldType\Relation\Value $fieldValue */
            $fieldValue = $content->getField('parent_node')->value;

            try {
                $destinationContent = $this->loadService->loadContent($fieldValue->destinationContentId);

                if ($destinationContent->contentInfo->published) {
                    $parentLocation = $this->loadService->loadLocation($destinationContent->mainLocationId);

                    if ($content->getField('item_url')->isEmpty() && $content->getField('item_object')->isEmpty()) {
                        $childItem->setName($parentLocation->id);
                    }

                    $criteria = array(
                        new Criterion\Visibility(Criterion\Visibility::VISIBLE),
                        new Criterion\ParentLocationId($parentLocation->id),
                    );

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

                    if (!$content->getField('limit')->isEmpty()) {
                        /** @var \eZ\Publish\Core\FieldType\Integer\Value $limit */
                        $limit = $content->getField('limit')->value;
                        if ($limit->value > 0) {
                            $query->limit = $limit->value;
                        }
                    }

                    $query->sortClauses = array(
                        $this->sortClauseHelper->getSortClauseBySortField(
                            $parentLocation->sortField,
                            $parentLocation->sortOrder
                        ),
                    );

                    $searchResult = $this->findService->findLocations($query);
                    $foundLocations = array_map(
                        function (SearchHit $searchHit) {
                            return $searchHit->valueObject;
                        },
                        $searchResult->searchHits
                    );

                    $this->addMenuItemsFromLocations($childItem, $foundLocations);
                } else {
                    $this->logger->error('[Relation menu] Menu item has related object that is not published.');
                }
            } catch (NotFoundException $e) {
                $this->logger->error($e->getMessage());
            }
        } elseif (!$content->getField('menu_items')->isEmpty()) {
            $this->generateFromRelationList(
                $childItem,
                $content,
                'menu_items'
            );
        }
    }

    /**
     * Adds menu items from array of Location objects.
     *
     * @param \Knp\Menu\ItemInterface $menuItem
     * @param \Netgen\EzPlatformSite\API\Values\Location[] $locations
     */
    protected function addMenuItemsFromLocations(ItemInterface $menuItem, array $locations = array())
    {
        foreach ($locations as $location) {
            if (!$location instanceof Location) {
                continue;
            }

            $menuItem->addChild(
                $location->id,
                array(
                    'label' => $location->contentInfo->name,
                    'uri' => $this->router->generate($location),
                    'attributes' => array(
                        'id' => 'menu-item-location-id-' . $location->id,
                    ),
                )
            );
        }
    }
}
