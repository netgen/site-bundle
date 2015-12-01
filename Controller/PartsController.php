<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;

class PartsController extends Controller
{
    /**
     * Action for rendering the gallery
     *
     * @param mixed $locationId
     * @param string $template
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewGallery( $locationId, $template )
    {
        $fieldHelper = $this->container->get( 'ezpublish.field_helper' );

        $location = $this->getRepository()->getLocationService()->loadLocation( $locationId );
        $content = $this->getRepository()->getContentService()->loadContent( $location->contentId );

        $contentList = $this->getChildren( $location );

        if ( !$fieldHelper->isFieldEmpty( $content, 'image' ) )
        {
            array_unshift( $contentList, $content );
        }

        return $this->render(
            $template,
            array(
                'content_list' => $contentList
            )
        );
    }

    /**
     * Action for rendering related items
     *
     * @param mixed $contentId
     * @param string $fieldDefinitionIdentifier
     * @param string $viewType
     * @param string $template
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewRelatedItems( $contentId, $fieldDefinitionIdentifier, $template, $viewType = 'line' )
    {
        $relatedItems = array();

        $fieldHelper = $this->container->get( 'ezpublish.field_helper' );
        $translationHelper = $this->container->get( 'ezpublish.translation_helper' );
        $locationService = $this->getRepository()->getLocationService();

        $content = $this->getRepository()->getContentService()->loadContent( $contentId );

        if ( isset( $content->fields[$fieldDefinitionIdentifier] ) && !$fieldHelper->isFieldEmpty( $content, $fieldDefinitionIdentifier ) )
        {
            /** @var \Netgen\Bundle\MoreBundle\Core\FieldType\RelationList\Value $fieldValue */
            $fieldValue = $translationHelper->getTranslatedField( $content, $fieldDefinitionIdentifier )->value;
            if ( !empty( $fieldValue->destinationLocationIds ) )
            {
                foreach ( $fieldValue->destinationLocationIds as $locationId )
                {
                    try
                    {
                        if ( !empty( $locationId ) )
                        {
                            $location = $locationService->loadLocation( $locationId );
                            if ( !$location->invisible )
                            {
                                $relatedItems[] = $location;
                            }
                        }
                    }
                    catch ( NotFoundException $e )
                    {
                        // Do nothing if there's no location
                        continue;
                    }
                }
            }
        }

        return $this->render(
            $template,
            array(
                'related_items' => $relatedItems,
                'view_type' => $viewType
            )
        );
    }

    /**
     *
     * Action for rendering related multimedia items
     * @deprecated DEPRECATED, use viewRelatedMultimediaItems() instead
     *
     * @param int $locationId
     * @param string $template
     * @param bool $includeChildrenImages
     * @param string $imageAliasName
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewRelatedMultimedia( $locationId, $template, $includeChildrenImages = false, $imageAliasName = null )
    {
        return $this->viewRelatedMultimediaItems( $locationId, $template, $includeChildrenImages, $imageAliasName );
    }

    /**
     * Action for rendering related multimedia items
     * If more than one multimedia item is found, it will display a slider
     * Items included:
     * 1. current content object - if we want multimedia slider to include some field value from the object itself (like image or video that are not in related_multimedia field),
     * we just need to add twig template to site bundle in /Resources/views/parts/related_multimedia_items/ with name pattern $contentTypeIdentifier.html.twig
     * (i.e. for ng_article content type:  /Resources/views/parts/related_multimedia_items/ng_Article.html.twig). Check already implemented templates for other content types
     * to see how the template logic for slider/non-slider is done
     * 2. children objects - if $includeChildren parameter is set, all children content objects will be added in the multimedia items list
     * 3. related objects from related_multimedia object relation field ( related images, images from related galleries, banners, videos )
     * - to enable this feature for some content type, add object relations field with content type identifier 'related_multimedia'
     *
     * @param int $locationId
     * @param string $template
     * @param bool $includeChildren
     * @param string $imageAliasName
     * @param array $contentTypeIdentifiers
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewRelatedMultimediaItems( $locationId, $template, $includeChildren = false, $imageAliasName = null, array $contentTypeIdentifiers = array( 'image' ) )
    {
        $fieldHelper = $this->container->get( 'ezpublish.field_helper' );
        $translationHelper = $this->container->get( 'ezpublish.translation_helper' );

        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();
        $contentTypeService = $repository->getContentTypeService();

        $location = $locationService->loadLocation( $locationId );
        $contentInfo = $location->getContentInfo();
        $contentTypeIdentifier = $contentTypeService->loadContentType( $contentInfo->contentTypeId )->identifier;
        $content = $contentService->loadContentByContentInfo( $contentInfo );
        $contentFields = $content->fields;

        $multimediaItems = array();

        // Add current location in the multimedia item list
        $multimediaItems[] = array( 'type' => $contentTypeIdentifier, 'content' => $content );

        // Get children objects and add them in multimedia item list
        if ( $includeChildren )
        {
            $galleryItems = $this->getChildren( $location, $contentTypeIdentifiers );
            if ( !empty( $galleryItems ) )
            {
                foreach ( $galleryItems as $galleryItemContent )
                {
                    $galleryItemContentTypeIdentifier = $contentTypeService->loadContentType( $galleryItemContent->id )->identifier;
                    $multimediaItems[] = array( 'type' => $galleryItemContentTypeIdentifier, 'content' => $galleryItemContent );
                }
            }
        }

        // Finally, check if related_multimedia field exists and has content
        $relatedMultimediaLocationIds = array();
        if ( array_key_exists( 'related_multimedia', $contentFields ) )
        {
            if ( !$fieldHelper->isFieldEmpty( $content, 'related_multimedia' ) )
            {
                $relatedMultimediaField = $translationHelper->getTranslatedField( $content, 'related_multimedia' )->value;

                // We need to work with location IDs, because we need to check if related object has location, to prevent
                // possible problems with related items in trash.
                // Also, we need location IDs for fetching images from related ng_gallery objects
                $relatedMultimediaLocationIds = !empty( $relatedMultimediaField->destinationLocationIds ) ? $relatedMultimediaField->destinationLocationIds : array();
            }
        }

        // If there are related multimedia objects
        if ( !empty( $relatedMultimediaLocationIds ) )
        {
            foreach ( $relatedMultimediaLocationIds as $relatedMultimediaLocationId )
            {
                try
                {
                    $relatedMultimediaLocation = $locationService->loadLocation( $relatedMultimediaLocationId );
                }
                catch ( NotFoundException $e )
                {
                    // Skip non-existing locations (item in trash or missing location due to some other reason)
                    continue;
                }

                $relatedMultimediaContent = $contentService->loadContent( $relatedMultimediaLocation->contentId );
                $relatedMultimediaContentInfo = $relatedMultimediaLocation->getContentInfo();

                if ( !$relatedMultimediaContentInfo->published )
                {
                    continue;
                }

                $relatedMultimediaContentTypeIdentifier = $contentTypeService->loadContentType( $relatedMultimediaContentInfo->contentTypeId )->identifier;

                // ng_gallery - Find children objects and add them in multimedia item list
                if ( $relatedMultimediaContentTypeIdentifier == 'ng_gallery' )
                {
                    $galleryItems = $this->getChildren( $relatedMultimediaLocation, $contentTypeIdentifiers );
                    if ( !empty( $galleryItems ) )
                    {
                        foreach ( $galleryItems as $galleryItemContent )
                        {
                            $galleryItemContentTypeIdentifier = $contentTypeService->loadContentType( $galleryItemContent->id )->identifier;
                            $multimediaItems[] = array( 'type' => $galleryItemContentTypeIdentifier, 'content' => $galleryItemContent );
                        }
                    }
                }
                else
                {
                    $multimediaItems[] = array( 'type' => $relatedMultimediaContentTypeIdentifier, 'content' => $relatedMultimediaContent );
                }
            }
        }

        return $this->render(
            $template,
            array(
                'content_id' => $content->id,
                'multimedia_items' => $multimediaItems,
                'alias_name' => $imageAliasName
            )
        );
    }

    /**
     * Helper method for fetching children items from specified location
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     * @param array $contentTypeIdentifiers
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Content[]
     */
    protected function getChildren( Location $location, array $contentTypeIdentifiers = array( 'image' ) )
    {
        $contentService = $this->getRepository()->getContentService();
        $query = new LocationQuery();
        $contentList = array();

        $criteria = array(
            new Criterion\ParentLocationId( $location->id ),
            new Criterion\Visibility( Criterion\Visibility::VISIBLE )
        );

        if ( !empty( $contentTypeIdentifiers ) )
        {
            $criteria[] = new Criterion\ContentTypeIdentifier( $contentTypeIdentifiers );
        }

        $query->filter = new Criterion\LogicalAnd( $criteria );

        $query->sortClauses = array(
            $this->container->get( 'ngmore.helper.sort_clause_helper' )->getSortClauseBySortField(
                $location->sortField,
                $location->sortOrder
            )
        );

        $result = $this->getRepository()->getSearchService()->findLocations( $query );

        foreach ( $result->searchHits as $searchHit )
        {
            $contentList[] = $contentService->loadContent( $searchHit->valueObject->contentId );
        }

        return $contentList;
    }

}
