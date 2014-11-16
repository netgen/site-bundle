<?php

namespace Netgen\Bundle\MoreBundle\Menu;

use Knp\Menu\ItemInterface;
use Knp\Menu\FactoryInterface;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\Helper\FieldHelper;
use eZ\Publish\Core\Helper\TranslationHelper;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper;
use Netgen\Bundle\MoreBundle\Core\FieldType\RelationList\Value as RelationListValue;

class RelationListMenuBuilder
{
    /**
     * @var \Knp\Menu\FactoryInterface
     */
    protected $factory;

    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * @var \eZ\Publish\Core\Helper\FieldHelper
     */
    protected $fieldHelper;

    /**
     * @var \eZ\Publish\Core\Helper\TranslationHelper
     */
    protected $translationHelper;

    /**
     * @var \Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper
     */
    protected $siteInfoHelper;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    /**
     * Constructor
     *
     * @param \Knp\Menu\FactoryInterface $factory
     * @param \eZ\Publish\API\Repository\Repository $repository
     * @param \eZ\Publish\Core\Helper\FieldHelper $fieldHelper
     * @param \eZ\Publish\Core\Helper\TranslationHelper $translationHelper
     * @param \Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper $siteInfoHelper
     * @param \Symfony\Component\Routing\RouterInterface $router
     */
    public function __construct(
        FactoryInterface $factory,
        Repository $repository,
        FieldHelper $fieldHelper,
        TranslationHelper $translationHelper,
        SiteInfoHelper $siteInfoHelper,
        RouterInterface $router
    )
    {
        $this->factory = $factory;
        $this->repository = $repository;
        $this->fieldHelper = $fieldHelper;
        $this->translationHelper = $translationHelper;
        $this->siteInfoHelper = $siteInfoHelper;
        $this->router = $router;
    }

    /**
     * Creates the relation list menu
     *
     * @param string $fieldDefIdentifier
     * @param mixed $contentId
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function createRelationListMenu( $fieldDefIdentifier, $contentId = null )
    {
        $menu = $this->factory->createItem( 'root' );

        if ( $contentId !== null )
        {
            $content = $this->repository->getContentService()->loadContent( $contentId );
        }
        else
        {
            $content = $this->siteInfoHelper->getSiteInfoContent();
        }

        $this->generateFromRelationList( $menu, $content, $fieldDefIdentifier );

        return $menu;
    }

    /**
     * Generates a menu item from a relation list in content
     *
     * @param \Knp\Menu\ItemInterface $menuItem
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     * @param string $fieldDefIdentifier
     */
    protected function generateFromRelationList( ItemInterface $menuItem, Content $content, $fieldDefIdentifier )
    {
        if ( $this->fieldHelper->isFieldEmpty( $content, $fieldDefIdentifier ) )
        {
            return;
        }

        $fieldValue = $this->translationHelper->getTranslatedField( $content, $fieldDefIdentifier )->value;
        if ( !$fieldValue instanceof RelationListValue )
        {
            return;
        }

        foreach ( $fieldValue->destinationLocationIds as $locationId )
        {
            try
            {
                $location = $this->repository->getLocationService()->loadLocation( $locationId );
            }
            catch ( NotFoundException $e )
            {
                continue;
            }

            $contentTypeIdentifier = $this->repository
                ->getContentTypeService()
                ->loadContentType( $location->contentInfo->contentTypeId )->identifier;

            if ( $contentTypeIdentifier == 'ng_shortcut' )
            {
                $this->generateFromNgShortcut( $menuItem, $location );
            }
            else if ( $contentTypeIdentifier == 'ng_menu_item' )
            {
                $this->generateFromNgMenuItem( $menuItem, $location );
            }
            else
            {
                $this->addMenuItemsFromLocations( $menuItem, array( $location ) );
            }
        }
    }

    /**
     * Generates a menu item from Location object of ng_shortcut content type
     *
     * @param \Knp\Menu\ItemInterface $menuItem
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     */
    protected function generateFromNgShortcut( ItemInterface $menuItem, Location $location )
    {
        $content = $this->repository->getContentService()->loadContent( $location->contentId );

        $uri = false;
        $menuItemId = $label = $this->translationHelper->getTranslatedContentName( $content );
        $attributes = array();
        $linkAttributes = array();

        if ( !$this->fieldHelper->isFieldEmpty( $content, 'url' ) )
        {
            /** @var \eZ\Publish\Core\FieldType\Url\Value $fieldValue */
            $fieldValue = $this->translationHelper->getTranslatedField( $content, 'url' )->value;
            if ( stripos( $fieldValue->link, 'http' ) === 0 )
            {
                $menuItemId = $uri = $fieldValue->link;

                $linkAttributes = array(
                    'target' => '_blank'
                );

                if ( !empty( $fieldValue->text ) )
                {
                    $linkAttributes['title'] = $fieldValue->text;
                }
            }
            else
            {
                $menuItemId = $uri = $this->router->generate(
                    'ez_legacy',
                    array(
                        'module_uri' => $fieldValue->link
                    )
                );

                if ( !empty( $fieldValue->text ) )
                {
                    $linkAttributes['title'] = $fieldValue->text;
                }
            }
        }
        else if ( !$this->fieldHelper->isFieldEmpty( $content, 'related_object' ) )
        {
            /** @var \eZ\Publish\Core\FieldType\Relation\Value $fieldValue */
            $fieldValue = $this->translationHelper->getTranslatedField( $content, 'related_object' )->value;

            try
            {
                $relatedContent = $this->repository->getContentService()->loadContentInfo( $fieldValue->destinationContentId );
                $relatedContentName = $this->translationHelper->getTranslatedContentNameByContentInfo( $relatedContent );

                $menuItemId = $relatedContent->mainLocationId;

                $uri = $this->router->generate(
                    'ez_urlalias',
                    array(
                        'contentId' => $relatedContent->id
                    )
                ) . $this->translationHelper->getTranslatedField( $content, 'internal_url_suffix' )->value->text;

                $label = $relatedContentName;

                $attributes = array(
                    'id' => 'menu-item-location-id-' . $relatedContent->mainLocationId
                );

                $linkAttributes = array(
                    'title' => $relatedContentName
                );
            }
            catch ( NotFoundException $e )
            {
                // do nothing
            }
        }

        $menuItem->addChild(
            $menuItemId,
            array(
                'uri' => $uri,
                'label' => $label,
                'attributes' => $attributes,
                'linkAttributes' => $linkAttributes
            )
        );
    }

    /**
     * Generates a menu item and potential children from Location object of ng_menu_item content type
     *
     * @param \Knp\Menu\ItemInterface $menuItem
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     */
    protected function generateFromNgMenuItem( ItemInterface $menuItem, Location $location )
    {
        $content = $this->repository->getContentService()->loadContent( $location->contentId );

        $uri = false;
        $menuItemId = $label = $this->translationHelper->getTranslatedContentName( $content );
        $attributes = array();
        $linkAttributes = array();

        if ( !$this->fieldHelper->isFieldEmpty( $content, 'item_url' ) )
        {
            /** @var \eZ\Publish\Core\FieldType\Url\Value $fieldValue */
            $fieldValue = $this->translationHelper->getTranslatedField( $content, 'item_url' )->value;
            if ( stripos( $fieldValue->link, 'http' ) === 0 )
            {
                $menuItemId = $uri = $fieldValue->link;

                $linkAttributes = array(
                    'target' => '_blank'
                );

                if ( !empty( $fieldValue->text ) )
                {
                    $linkAttributes['title'] = $fieldValue->text;
                }
            }
            else
            {
                $menuItemId = $uri = $this->router->generate(
                    'ez_legacy',
                    array(
                        'module_uri' => $fieldValue->link
                    )
                );

                if ( !empty( $fieldValue->text ) )
                {
                    $linkAttributes['title'] = $fieldValue->text;
                }
            }
        }
        else if ( !$this->fieldHelper->isFieldEmpty( $content, 'item_object' ) )
        {
            /** @var \eZ\Publish\Core\FieldType\Relation\Value $fieldValue */
            $fieldValue = $this->translationHelper->getTranslatedField( $content, 'item_object' )->value;

            try
            {
                $relatedContent = $this->repository->getContentService()->loadContentInfo( $fieldValue->destinationContentId );
                $relatedContentName = $this->translationHelper->getTranslatedContentNameByContentInfo( $relatedContent );

                $menuItemId = $relatedContent->mainLocationId;

                $uri = $this->router->generate(
                    'ez_urlalias',
                    array(
                        'contentId' => $relatedContent->id
                    )
                );

                $label = $relatedContentName;

                $attributes = array(
                    'id' => 'menu-item-location-id-' . $relatedContent->mainLocationId
                );

                $linkAttributes = array(
                    'title' => $relatedContentName
                );
            }
            catch ( NotFoundException $e )
            {
                // do nothing
            }
        }

        $childItem = $menuItem->addChild(
            $menuItemId,
            array(
                'uri' => $uri,
                'label' => $label,
                'attributes' => $attributes,
                'linkAttributes' => $linkAttributes
            )
        );

        if ( !$this->fieldHelper->isFieldEmpty( $content, 'parent_node' ) )
        {
            // @TODO: Generate links for child locations
        }
        else if ( !$this->fieldHelper->isFieldEmpty( $content, 'menu_items' ) )
        {
            $this->generateFromRelationList(
                $childItem,
                $content,
                'menu_items'
            );
        }
    }

    /**
     * Adds menu items from array of Location objects
     *
     * @param \Knp\Menu\ItemInterface $menuItem
     * @param \eZ\Publish\API\Repository\Values\Content\Location[] $locations
     */
    protected function addMenuItemsFromLocations( ItemInterface $menuItem, array $locations = array() )
    {
        foreach ( $locations as $location )
        {
            if ( !$location instanceof Location )
            {
                continue;
            }

            $menuItem->addChild(
                $location->id,
                array(
                    'label' => $this->translationHelper->getTranslatedContentNameByContentInfo(
                        $location->contentInfo
                    ),
                    'uri' => $this->router->generate(
                        'ez_urlalias',
                        array(
                            'locationId' => $location->id
                        )
                    ),
                    'attributes' => array(
                        'id' => 'menu-item-location-id-' . $location->id
                    )
                )
            );
        }
    }
}
