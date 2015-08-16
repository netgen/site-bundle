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

        $contentList = $this->getChildrenImages( $location );

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
     * Action for rendering related multimedia items
     * If more than one multimedia item is found, it will display a slider
     * Items included:
     * image field from the object ( if exists and has content ),
     * children images ( only if the current location is a ng_gallery )
     * any media items from related multimedia objects ( related images, images from related galleries, banners, videos )
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

        // Get children image objects and add them in multimedia item list
        if ( $includeChildrenImages )
        {
            $galleryImages = $this->getChildrenImages( $location );
            if ( !empty( $galleryImages ) )
            {
                foreach ( $galleryImages as $galleryImage )
                {
                    $multimediaItems[] = array( 'type' => 'image', 'content' => $galleryImage );
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

                // we need to work with location IDs, because we need to check if related object has location,
                // to prevent possible problems with related items in trash.
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

                // ng_gallery - Find children ng_image objects and add them in multimedia item list
                if ( $relatedMultimediaContentTypeIdentifier == 'ng_gallery' )
                {
                    $galleryImages = $this->getChildrenImages( $relatedMultimediaLocation );
                    if ( !empty( $galleryImages ) )
                    {
                        foreach ( $galleryImages as $galleryImage )
                        {
                            $multimediaItems[] = array( 'type' => 'image', 'content' => $galleryImage );
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
     * Helper method for fetching images from specified location
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Content[]
     */
    protected function getChildrenImages( Location $location )
    {
        $contentService = $this->getRepository()->getContentService();
        $query = new LocationQuery();
        $images = array();

        $query->criterion = new Criterion\LogicalAnd(
            array(
                new Criterion\ParentLocationId( $location->id ),
                new Criterion\Visibility( Criterion\Visibility::VISIBLE ),
                new Criterion\ContentTypeIdentifier( 'image' )
            )
        );

        $query->sortClauses = array(
            $this->container->get( 'ngmore.helper.sort_clause_helper' )->getSortClauseBySortField(
                $location->sortField,
                $location->sortOrder
            )
        );

        $result = $this->getRepository()->getSearchService()->findLocations( $query );

        foreach ( $result->searchHits as $searchHit )
        {
            $images[] = $contentService->loadContent( $searchHit->valueObject->contentId );
        }

        return $images;
    }
}
